<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Requests\StoreReenrollmentRequest;
use App\Services\FeeService;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function create(FeeService $feeService)
    {
        $this->authorize('creer-inscription');

        $classrooms = Classroom::all();
        $activeYear = SchoolYear::where('is_active', true)->first();

        if (! $activeYear) {
            return back()->withErrors(['error' => 'Aucune année scolaire active.']);
        }

        // Bibliothèque des frais : source de vérité unique pour les montants
        // d'inscription/mensualité/cantine/transport, par classe, pour l'année active.
        $feeLibrary = $classrooms->mapWithKeys(
            fn (Classroom $classroom) => [$classroom->id => $feeService->getCurrentFeeAmounts($classroom->id, $activeYear->id)]
        );

        return view('registrations.create', [
            'classrooms' => $classrooms,
            'activeYear' => $activeYear,
            'parents' => ParentModel::actifs()->get(),
            'student' => new User(['is_active' => true]),
            'feeLibrary' => $feeLibrary,
        ]);
    }

    public function store(StoreRegistrationRequest $request, StudentEnrollmentService $enrollmentService)
    {
        $this->authorize('creer-inscription');

        $validated = $request->validated();

        if (! SchoolYear::where('is_active', true)->exists()) {
            return back()->withErrors(['error' => 'Aucune année scolaire active.'])->withInput();
        }

        $validated['is_active'] = $request->boolean('is_active', false);

        try {
            $student = $enrollmentService->enroll(
                $validated,
                $request->file('photo'),
                auth()->id(),
                $request->user()->hasAnyRole(['super-admin', 'manager-comptable'])
            );
        } catch (\Illuminate\Database\QueryException $e) {
            return back()
                ->withErrors(['error' => "Une erreur est survenue lors de l'enregistrement, probablement due à une double soumission. Vérifiez que l'élève n'a pas déjà été inscrit avant de réessayer."])
                ->withInput();
        }

        $message = 'Inscription élève réussie. Matricule : '.$student->matricule.'.';

        $parentCredentials = $enrollmentService->getParentCredentials();
        if ($parentCredentials) {
            $message .= ' | Compte parent — Matricule : '.$parentCredentials['matricule'].' | Email : '.$parentCredentials['email'].'.';
        }

        return redirect()
            ->route('dashboard')
            ->with('success', $message)
            ->with('temp_credentials', [
                'student_password' => $enrollmentService->studentTemporaryPassword,
                'parent_password' => $parentCredentials['password'] ?? null,
                'parent_matricule' => $parentCredentials['matricule'] ?? null,
            ])
            ->with('warning', 'Ces identifiants temporaires sont affichés une seule fois. Notez-les avant de quitter la page.');
    }

    /**
     * Formulaire de réinscription : recherche un élève existant par matricule et
     * réutilise automatiquement son identité/historique pour la nouvelle année.
     */
    public function reenrollSearch(Request $request, FeeService $feeService)
    {
        $this->authorize('voir-inscriptions');

        $classrooms = Classroom::all();
        $activeYear = SchoolYear::where('is_active', true)->first();

        if (! $activeYear) {
            return back()->withErrors(['error' => 'Aucune année scolaire active.']);
        }

        $feeLibrary = $classrooms->mapWithKeys(
            fn (Classroom $classroom) => [$classroom->id => $feeService->getCurrentFeeAmounts($classroom->id, $activeYear->id)]
        );

        $student = null;
        $lastRegistration = null;
        $alreadyRegistered = false;
        $archived = false;
        $searchedMatricule = $request->input('matricule');

        if ($request->filled('matricule')) {
            // withTrashed() : un élève archivé ne doit pas afficher le même message
            // générique « aucun élève trouvé » qu'un matricule inconnu — il doit
            // d'abord être restauré (voir StudentController::restore()) avant de
            // pouvoir être réinscrit.
            $student = User::withTrashed()
                ->where(function ($query) {
                    $query->where('role', 'eleve')->orWhereHas('roles', fn ($q) => $q->where('name', 'eleve'));
                })
                ->where('matricule', $searchedMatricule)
                ->first();

            if ($student && $student->trashed()) {
                $archived = true;
                $student = null;
            } elseif ($student) {
                $lastRegistration = Registration::with(['classroom', 'schoolYear'])
                    ->where('user_id', $student->id)
                    ->latest('registration_date')
                    ->first();

                $alreadyRegistered = Registration::where('user_id', $student->id)
                    ->where('school_year_id', $activeYear->id)
                    ->exists();
            }
        }

        return view('registrations.reinscription', [
            'activeYear' => $activeYear,
            'classrooms' => $classrooms,
            'feeLibrary' => $feeLibrary,
            'student' => $student,
            'lastRegistration' => $lastRegistration,
            'alreadyRegistered' => $alreadyRegistered,
            'archived' => $archived,
            'searchedMatricule' => $searchedMatricule,
        ]);
    }

    public function storeReenrollment(StoreReenrollmentRequest $request, StudentEnrollmentService $enrollmentService)
    {
        $this->authorize('creer-inscription');

        $validated = $request->validated();

        $student = User::findOrFail($validated['user_id']);

        if (! $student->isStudent()) {
            return back()->withErrors(['user_id' => "Ce compte n'est pas un compte élève."])->withInput();
        }

        if (! SchoolYear::where('is_active', true)->exists()) {
            return back()->withErrors(['error' => 'Aucune année scolaire active.'])->withInput();
        }

        $validated['is_active'] = $request->boolean('is_active', false);

        try {
            $registration = $enrollmentService->reenroll(
                $student,
                $validated,
                $request->user()->hasAnyRole(['super-admin', 'manager-comptable'])
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()
                ->withErrors(['error' => "Cet élève est déjà inscrit pour l'année scolaire active (probablement une double soumission)."])
                ->withInput();
        }

        $message = "Réinscription réussie pour {$student->name}. Matricule d'inscription : {$registration->matricule}";

        return redirect()->route('dashboard')->with('success', $message);
    }
}
