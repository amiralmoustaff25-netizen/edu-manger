<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Requests\UpdateStudentStatusRequest;
use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\FeeService;
use App\Services\StudentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\ImageManagerStatic as Image;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('voir-eleves');

        $students = User::query()
            ->students()
            ->with(['latestRegistration.classroom', 'latestRegistration.schoolYear'])
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
            ->when($request->filled('status'), function ($query) use ($request) {
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
            'classrooms' => Classroom::orderBy('name')->get(),
            'filters' => $request->only(['search', 'classroom_id', 'status']),
        ]);
    }

    /**
     * Process and store student photo.
     */
    private function processStudentPhoto($photo, User $user): ?string
    {
        if (!$photo) {
            return null;
        }

        // Delete old photo if exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $filename = $user->matricule . '_' . time() . '.jpg';
        $path = 'photos/eleves/' . $filename;

        try {
            if (extension_loaded('imagick')) {
                Image::configure(['driver' => 'imagick']);
            }

            $image = Image::make($photo)
                ->fit(150, 150)
                ->encode('jpg', 90);

            Storage::disk('public')->put($path, $image);
        } catch (\Throwable $e) {
            $ext = $photo->getClientOriginalExtension() ?: 'jpg';
            $filename = $user->matricule . '_' . time() . '.' . $ext;
            $path = 'photos/eleves/' . $filename;
            Storage::disk('public')->putFileAs('photos/eleves', $photo, $filename);
        }

        return $path;
    }

    public function show(User $student, FeeService $feeService): View
    {
        $this->authorize('voir-detail-eleve', $student);

        abort_unless($student->isStudent(), 404);

        $this->ensureTeacherIsAssignedToStudent($student);

        $student->load([
            'parents',
            'notes.matiere',
            'attendances.classroom',
            'attendances.recordedBy',
            'sanctions.author',
            'documents' => fn ($query) => $query->latest(),
            'documents.uploadedBy',
            'registrations' => fn ($query) => $query->with(['classroom', 'schoolYear', 'payments', 'discounts.appliedBy'])->latest(),
        ]);

        $currentRegistration = $student->registrations->first();
        $financialSituation = $currentRegistration
            ? $feeService->getFinancialSituation($currentRegistration)
            : ['expected' => 0, 'paid' => 0, 'remaining' => 0, 'overdue' => 0, 'next_due' => null, 'monthly_fee' => 0];

        return view('students.show', [
            'student' => $student,
            'currentRegistration' => $currentRegistration,
            'classrooms' => Classroom::orderBy('name')->get(),
            'totalPaid' => $financialSituation['paid'],
            'remainingBalance' => $financialSituation['remaining'],
            'financialSituation' => $financialSituation,
        ]);
    }

    public function edit(User $student): View
    {
        $this->authorize('voir-detail-eleve', $student);

        abort_unless($student->isStudent(), 404);

        $student->load(['parents', 'latestRegistration']);

        return view('students.edit', [
            'student' => $student,
            'classrooms' => Classroom::all(),
            'parents' => ParentModel::actifs()->get(),
            'activeYear' => SchoolYear::where('is_active', true)->first(),
        ]);
    }

    public function update(UpdateStudentRequest $request, User $student): RedirectResponse
    {
        $this->authorize('voir-detail-eleve', $student);

        abort_unless($student->isStudent(), 404);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $student, $request) {
            $updateData = [
                'name' => $validated['nom'] . ' ' . $validated['prenom'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'],
                'cycle' => $validated['cycle'],
                'telephone' => $validated['telephone'] ?? null,
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'sexe' => $validated['sexe'],
                'nationalite' => $validated['nationalite'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
            ];

            // Handle photo deletion
            if (!empty($validated['supprimer_photo'])) {
                Gate::authorize('remove-photo-eleve');
                if ($student->profile_photo_path) {
                    Storage::disk('public')->delete($student->profile_photo_path);
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
                    if (!empty($parentData['parent_id'])) {
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

    public function destroy(User $student): RedirectResponse
    {
        $this->authorize('voir-eleves');

        abort_unless($student->isStudent(), 404);

        $student->update(['is_active' => false]);
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Élève désactivé et archivé.');
    }

    public function transfer(TransferStudentRequest $request, User $student)
    {
        $this->authorize('transferer-eleve', $student);

        abort_unless($student->isStudent(), 404);

        $validated = $request->validated();

        $registration = Registration::where('user_id', $student->id)->findOrFail($validated['registration_id']);
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

    public function removePhoto(User $student): RedirectResponse
    {
        Gate::authorize('remove-photo-eleve');

        abort_unless($student->isStudent(), 404);

        if ($student->profile_photo_path) {
            Storage::disk('public')->delete($student->profile_photo_path);
            $student->update(['profile_photo_path' => null]);
        }

        return back()->with('success', 'Photo de l\'élève supprimée avec succès.');
    }

    /**
     * Un professeur ne doit consulter que les élèves d'une classe qui lui est
     * assignée. La permission 'voir-detail-eleve' est nécessaire pour franchir
     * le middleware de route mais reste globale : ce contrôle par instance
     * complète la policy pour ce rôle précis.
     */
    private function ensureTeacherIsAssignedToStudent(User $student): void
    {
        $user = auth()->user();

        if (! $user->hasRole('professeur') || $user->hasAnyRole(['super-admin', 'admin'])) {
            return;
        }

        $teacher = $user->teacher;
        $classroomId = optional($student->latestRegistration)->classroom_id;

        abort_unless(
            $teacher && $classroomId && $teacher->classrooms()->where('classrooms.id', $classroomId)->exists(),
            403,
            "Vous n'êtes pas autorisé à consulter le dossier de cet élève."
        );
    }

    private function normalizeOptions(?array $options): array
    {
        return collect($options ?? [])
            ->only(['cantine', 'transport', 'internat'])
            ->map(fn ($value) => (bool) $value)
            ->toArray();
    }
}
