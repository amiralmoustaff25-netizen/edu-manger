<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
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

    public function store(Request $request, StudentEnrollmentService $enrollmentService)
    {
        $this->authorize('creer-inscription');

        $notTrashed = fn ($query) => $query->whereNull('deleted_at');

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where($notTrashed)],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'cycle' => ['required', 'in:primaire,college,lycee'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'registration_fee_paid' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*' => ['boolean'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'medical_notes' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'parents' => ['sometimes', 'array'],
            'parents.*.parent_id' => ['nullable', 'exists:parents,id'],
            'parents.*.lien_parente' => ['nullable', 'string', 'max:255'],
            'parents.*.est_responsable_financier' => ['nullable', 'boolean'],
            'parents.*.est_contact_urgence' => ['nullable', 'boolean'],

            'parent_nom' => ['nullable', 'required_with:parent_prenom,parent_email', 'string', 'max:255'],
            'parent_prenom' => ['nullable', 'required_with:parent_nom', 'string', 'max:255'],
            'parent_email' => [
                'nullable', 'required_with:parent_nom', 'email', 'max:255',
                Rule::unique('users', 'email')->where($notTrashed),
                Rule::unique('parents', 'email')->where($notTrashed),
            ],
            'parent_telephone' => ['nullable', 'string', 'max:20'],
            'parent_adresse' => ['nullable', 'string', 'max:500'],
            'parent_profession' => ['nullable', 'string', 'max:255'],
            'parent_lien_parente' => ['nullable', 'in:Pere,Mere,Tuteur,Tutrice,Autre'],
            'parent_est_responsable_financier' => ['nullable', 'boolean'],
            'parent_est_contact_urgence' => ['nullable', 'boolean'],
        ]);

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

        $message = 'Inscription élève réussie. Matricule : '.$student->matricule.' | Mot de passe temporaire : '.$enrollmentService->studentTemporaryPassword;

        $parentCredentials = $enrollmentService->getParentCredentials();
        if ($parentCredentials) {
            $message .= ' | Compte parent — Matricule : '.$parentCredentials['matricule'].' | Email : '.$parentCredentials['email'].' | Mot de passe temporaire : '.$parentCredentials['password'];
        }

        return redirect()->route('dashboard')->with('success', $message);
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

    public function storeReenrollment(Request $request, StudentEnrollmentService $enrollmentService)
    {
        $this->authorize('creer-inscription');

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'registration_fee_paid' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'options' => ['sometimes', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

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
