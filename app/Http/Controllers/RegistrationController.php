<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function create()
    {
        $classrooms = Classroom::all();
        $activeYear = SchoolYear::where('is_active', true)->first();

        if (! $activeYear) {
            return back()->withErrors(['error' => 'Aucune année scolaire active.']);
        }

        return view('registrations.create', [
            'classrooms' => $classrooms,
            'activeYear' => $activeYear,
            'parents' => ParentModel::actifs()->get(),
            'student' => new User(['is_active' => true]),
        ]);
    }

    public function store(Request $request, StudentEnrollmentService $enrollmentService)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'cycle' => ['required', 'in:primaire,college,lycee'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'role' => ['required', 'in:eleve'],
            'is_active' => ['nullable', 'boolean'],
            'registration_fee_paid' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'parents' => ['sometimes', 'array'],
            'parents.*.parent_id' => ['nullable', 'exists:parents,id'],
            'parents.*.lien_parente' => ['nullable', 'string', 'max:255'],
            'parents.*.est_responsable_financier' => ['nullable', 'boolean'],
            'parents.*.est_contact_urgence' => ['nullable', 'boolean'],

            'parent_nom' => ['nullable', 'required_with:parent_prenom,parent_email', 'string', 'max:255'],
            'parent_prenom' => ['nullable', 'required_with:parent_nom', 'string', 'max:255'],
            'parent_email' => ['nullable', 'required_with:parent_nom', 'email', 'max:255', Rule::unique('users', 'email'), Rule::unique('parents', 'email')],
            'parent_telephone' => ['nullable', 'string', 'max:20'],
            'parent_adresse' => ['nullable', 'string', 'max:500'],
            'parent_profession' => ['nullable', 'string', 'max:255'],
            'parent_lien_parente' => ['nullable', 'in:Pere,Mere,Tuteur,Tutrice,Autre'],
            'parent_est_responsable_financier' => ['nullable', 'boolean'],
            'parent_est_contact_urgence' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $student = $enrollmentService->enroll(
            $validated,
            $request->file('photo'),
            auth()->id()
        );

        $message = 'Inscription élève réussie. Matricule : '.$student->matricule.' | Mot de passe temporaire : password';

        $parentCredentials = $enrollmentService->getParentCredentials();
        if ($parentCredentials) {
            $message .= ' | Compte parent — Matricule : '.$parentCredentials['matricule'].' | Email : '.$parentCredentials['email'].' | Mot de passe temporaire : '.$parentCredentials['password'];
        }

        return redirect()->route('dashboard')->with('success', $message);
    }
}
