<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Requests\UpdateStudentStatusRequest;
use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\StudentClassHistory;
use App\Models\User;
use App\Services\FeeService;
use App\Services\SchoolYearContext;
use App\Services\SchoolYearGuardService;
use App\Services\StudentPhotoService;
use App\Services\StudentStatusService;
use App\Support\StudentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request, SchoolYearContext $context): View
    {
        $this->authorize('voir-eleves');

        $viewingYear = $context->current();

        $students = User::query()
            ->students()
            // withTrashed() : sans ça, le filtre "Archivé" ne peut structurellement
            // jamais rien retourner (même bug déjà corrigé pour Utilisateurs/Parents).
            ->withTrashed()
            ->with(['latestRegistration.classroom', 'latestRegistration.schoolYear'])
            ->when($viewingYear, function ($query) use ($viewingYear) {
                // Sans ce filtre, la liste mélangeait indéfiniment tous les comptes élèves
                // jamais créés, quelle que soit l'année : le sélecteur d'année transverse
                // n'aurait alors aucun effet visible ici.
                $query->whereHas('registrations', fn ($query) => $query->where('school_year_id', $viewingYear->id));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('matricule', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('registrations', fn ($query) => $query->where('matricule', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('classroom_id'), function ($query) use ($request) {
                $query->whereHas('registrations', fn ($query) => $query->where('classroom_id', $request->integer('classroom_id')));
            })
            ->when($request->string('status')->toString() === 'archived', function ($query) {
                $query->whereNotNull('deleted_at');
            }, function ($query) use ($request) {
                $query->whereNull('deleted_at');

                $status = $request->string('status')->toString();

                if (in_array($status, ['pending', 'active'], true)) {
                    $query->whereHas('registrations', fn ($query) => $query->where('status', $status));
                }

                if ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'classrooms' => $viewingYear
                ? Classroom::where('school_year_id', $viewingYear->id)->orderBy('name')->get()
                : Classroom::orderBy('name')->get(),
            'filters' => $request->only(['search', 'classroom_id', 'status']),
            'viewingYear' => $viewingYear,
        ]);
    }

    /**
     * Process and store student photo.
     *
     * SEC-03 : délègue à StudentPhotoService (disque privé `local`,
     * même logique que l'inscription) plutôt que de dupliquer un
     * stockage sur le disque public.
     */
    private function processStudentPhoto($photo, User $user): ?string
    {
        if (! $photo) {
            return null;
        }

        // Delete old photo if exists
        if ($user->profile_photo_path) {
            Storage::disk('local')->delete($user->profile_photo_path);
        }

        return app(StudentPhotoService::class)->store($photo, $user->matricule);
    }

    /**
     * SEC-03 : sert la photo d'un élève depuis le disque privé, en
     * réutilisant la même règle d'accès que la fiche élève (voirDetailEleve),
     * plutôt que de l'exposer par une URL publique non authentifiée.
     */
    public function photo(User $student): StreamedResponse
    {
        $this->authorize('voir-detail-eleve', $student);

        abort_unless($student->profile_photo_path, 404);
        abort_unless(Storage::disk('local')->exists($student->profile_photo_path), 404);

        return Storage::disk('local')->response($student->profile_photo_path);
    }

    public function show(User $student, FeeService $feeService): View
    {
        $this->authorize('voir-detail-eleve', $student);

        abort_unless($student->isStudent(), 404);

        abort_unless(
            auth()->user()->isTeacherAssignedToStudent($student),
            403,
            "Vous n'êtes pas autorisé à consulter le dossier de cet élève."
        );

        $student->load([
            'parents',
            'notes.matiere',
            'attendances.classroom',
            'attendances.recordedBy',
            'sanctions.author',
            'documents' => fn ($query) => $query->latest(),
            'documents.uploadedBy',
            'registrations' => fn ($query) => $query->with(['classroom', 'schoolYear', 'payments', 'discounts.appliedBy'])->latest(),
            'classHistories' => fn ($query) => $query->with('classroom', 'schoolYear')->latest('id'),
        ]);

        $currentRegistration = $student->registrations->first();
        $financialSituation = $currentRegistration
            ? $feeService->getFinancialSituation($currentRegistration)
            : ['expected' => 0, 'paid' => 0, 'remaining' => 0, 'overdue' => 0, 'next_due' => null, 'monthly_fee' => 0];

        return view('students.show', [
            'student' => $student,
            'currentRegistration' => $currentRegistration,
            // Scopé à l'année de $currentRegistration : c'est la classe de CETTE
            // inscription précise que le formulaire "Changer de classe" modifie
            // (voir transfer()) — un mélange de toutes les années la corromprait.
            'classrooms' => $currentRegistration
                ? Classroom::where('school_year_id', $currentRegistration->school_year_id)->orderBy('name')->get()
                : Classroom::orderBy('name')->get(),
            'totalPaid' => $financialSituation['paid'],
            'remainingBalance' => $financialSituation['remaining'],
            'financialSituation' => $financialSituation,
        ]);
    }

    public function edit(User $student): View
    {
        $this->authorize('modifier-eleve', $student);

        abort_unless($student->isStudent(), 404);

        $student->load(['parents', 'latestRegistration']);

        // Scopé à l'année de l'inscription modifiée (et non à toutes les années
        // confondues) : ce formulaire modifie classroom_id sur cette inscription
        // précise, un mélange de classes d'autres années permettrait de la
        // rattacher par erreur à une classe qui n'appartient pas à son année.
        $registrationYearId = $student->latestRegistration?->school_year_id
            ?? SchoolYear::where('is_active', true)->value('id');

        return view('students.edit', [
            'student' => $student,
            'classrooms' => Classroom::where('school_year_id', $registrationYearId)->orderBy('name')->get(),
            'parents' => ParentModel::actifs()->get(),
            'activeYear' => SchoolYear::where('is_active', true)->first(),
        ]);
    }

    public function update(UpdateStudentRequest $request, User $student, SchoolYearGuardService $schoolYearGuard): RedirectResponse
    {
        $this->authorize('modifier-eleve', $student);

        abort_unless($student->isStudent(), 404);

        // MET-01 : ce contrôleur ne vérifiait jamais si l'année scolaire de
        // l'inscription modifiée était clôturée, contrairement aux modules
        // financiers (paiements, frais). Un changement de classe/frais sur
        // une année déjà clôturée corromprait silencieusement l'historique
        // officiel utilisé pour les bulletins archivés et les statistiques
        // d'effectifs passées.
        $schoolYearGuard->assertNotLocked($student->latestRegistration?->schoolYear);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $student, $request) {
            $updateData = [
                'name' => $validated['nom'].' '.$validated['prenom'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'],
                'cycle' => $validated['cycle'],
                'telephone' => $validated['telephone'] ?? null,
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'sexe' => $validated['sexe'],
                'nationalite' => $validated['nationalite'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'medical_notes' => $validated['medical_notes'] ?? null,
                'allergies' => $validated['allergies'] ?? null,
            ];

            // Handle photo deletion
            if (! empty($validated['supprimer_photo'])) {
                Gate::authorize('remove-photo-eleve');
                if ($student->profile_photo_path) {
                    Storage::disk('local')->delete($student->profile_photo_path);
                }
                $updateData['profile_photo_path'] = null;
            }

            // Process new photo if uploaded
            if ($request->hasFile('photo')) {
                Gate::authorize('upload-photo-eleve');
                $photoPath = $this->processStudentPhoto($request->file('photo'), $student);
                $updateData['profile_photo_path'] = $photoPath;
            }

            $student->update($updateData);

            $currentRegistration = $student->latestRegistration;
            if ($currentRegistration) {
                $currentRegistration->update([
                    'classroom_id' => $validated['classroom_id'],
                    'registration_fee_paid' => $validated['registration_fee_paid'] ?? $currentRegistration->registration_fee_paid,
                    'monthly_fee' => $validated['monthly_fee'] ?? $currentRegistration->monthly_fee,
                    'options' => $this->normalizeOptions($validated['options'] ?? []),
                ]);
            }

            if (isset($validated['parents'])) {
                $parentSync = [];
                foreach ($validated['parents'] as $parentData) {
                    if (! empty($parentData['parent_id'])) {
                        $parentSync[$parentData['parent_id']] = [
                            'lien_parente' => $parentData['lien_parente'] ?? null,
                            'est_responsable_financier' => $parentData['est_responsable_financier'] ?? false,
                            'est_contact_urgence' => $parentData['est_contact_urgence'] ?? false,
                        ];
                    }
                }
                $student->parents()->sync($parentSync);
            }
        });

        return redirect()->route('students.show', $student)->with('success', 'Élève mis à jour avec succès.');
    }

    public function destroy(User $student, StudentStatusService $studentStatusService): RedirectResponse
    {
        $this->authorize('supprimer-eleve', $student);

        abort_unless($student->isStudent(), 404);

        DB::transaction(function () use ($student, $studentStatusService) {
            $registration = $student->latestRegistration;

            // Une inscription encore ouverte (non terminale) doit être clôturée en
            // même temps que le compte est archivé, sinon elle continue de compter
            // comme "active"/"en attente" dans les agrégats financiers du dashboard
            // alors que l'élève n'apparaît plus nulle part dans l'UI.
            if ($registration && ! StudentStatus::isTerminal($registration->status)) {
                $targetStatus = $registration->status === StudentStatus::PENDING
                    ? StudentStatus::CANCELLED
                    : StudentStatus::WITHDRAWN;

                $studentStatusService->transition($registration, $targetStatus, 'Archivage du compte élève.');
            }

            $student->update(['is_active' => false]);
            $student->delete();
        });

        return redirect()->route('students.index')->with('success', 'Élève désactivé et archivé.');
    }

    public function restore(int $id): RedirectResponse
    {
        $student = User::withTrashed()->findOrFail($id);

        $this->authorize('supprimer-eleve', $student);

        abort_unless($student->isStudent(), 404);

        $student->restore();

        // L'inscription a été clôturée (retirée/annulée) à l'archivage : elle n'est
        // pas réactivée automatiquement, car ce statut est volontairement terminal
        // dans la machine à états (voir StudentStatus::TRANSITIONS). Pour faire
        // réapparaître l'élève comme actif, utiliser la Réinscription.
        return redirect()->route('students.index')
            ->with('success', 'Élève restauré. Utilisez « Réinscription » pour lui créer une nouvelle inscription active.');
    }

    public function transfer(TransferStudentRequest $request, User $student, SchoolYearGuardService $schoolYearGuard)
    {
        $this->authorize('transferer-eleve', $student);

        abort_unless($student->isStudent(), 404);

        $validated = $request->validated();

        $registration = Registration::where('user_id', $student->id)->findOrFail($validated['registration_id']);

        // MET-01 : transfert possible pour N'IMPORTE QUELLE inscription du dossier
        // (pas seulement celle de l'année active) — sans ce contrôle, un transfert
        // sur une inscription d'une année clôturée corromprait l'historique de
        // classe archivé pour cette année.
        $schoolYearGuard->assertNotLocked($registration->schoolYear);

        // Historise la classe/année quittée avant le changement, sinon
        // StudentClassHistory ne serait jamais alimenté (voir aussi
        // StudentEnrollmentService::reenroll()).
        StudentClassHistory::create([
            'user_id' => $student->id,
            'classroom_id' => $registration->classroom_id,
            'school_year_id' => $registration->school_year_id,
            'annee_scolaire' => $registration->academic_year,
        ]);

        $registration->update(['classroom_id' => $validated['classroom_id']]);

        return back()->with('success', 'Classe de l\'élève mise à jour.');
    }

    public function updateStatus(UpdateStudentStatusRequest $request, User $student, StudentStatusService $studentStatusService)
    {
        $this->authorize('modifier-statut-eleve', $student);

        abort_unless($student->isStudent(), 404);

        $validated = $request->validated();

        $registration = Registration::where('user_id', $student->id)->findOrFail($validated['registration_id']);

        $studentStatusService->transition($registration, $validated['status'], $validated['status_reason'] ?? null);

        return back()->with('success', 'Statut de l\'élève mis à jour.');
    }

    private function normalizeOptions(?array $options): array
    {
        return collect($options ?? [])
            ->only(['cantine', 'transport', 'internat'])
            ->map(fn ($value) => (bool) $value)
            ->toArray();
    }
}
