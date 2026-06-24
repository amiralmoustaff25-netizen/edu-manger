<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\User;
use App\Models\Registration;
use App\Models\Payment;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ParentController extends Controller
{
    /**
     * Liste paginée des parents avec recherche et filtrage
     */
    public function index(Request $request)
    {
        $parents = ParentModel::query()
            ->with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->search($search);
            })
            ->when($request->filled('statut'), function ($query) use ($request) {
                $statut = $request->string('statut')->toString();
                if (in_array($statut, ['actif', 'en_attente_activation', 'archive'])) {
                    $query->where('statut', $statut);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('parents.index', [
            'parents' => $parents,
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    /**
     * Afficher le formulaire de création d'un parent
     */
    public function create()
    {
        return view('parents.create', [
            'parent' => new ParentModel(['statut' => 'en_attente_activation']),
        ]);
    }

    /**
     * Créer un nouveau parent avec son compte utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:parents,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string'],
            'profession' => ['nullable', 'string', 'max:255'],
            'statut' => ['required', Rule::in(['actif', 'en_attente_activation', 'archive'])],
        ]);

        // Vérifier si l'email existe déjà dans users
        if (User::where('email', $validated['email'])->exists()) {
            return back()->withErrors(['email' => 'Cet email est déjà utilisé par un compte utilisateur.'])->withInput();
        }

        // Créer le compte utilisateur associé
        $user = User::create([
            'name' => $validated['nom'] . ' ' . $validated['prenom'],
            'email' => $validated['email'],
            'password' => Hash::make('password'),
            'role' => 'parent',
            'is_active' => $validated['statut'] === 'actif',
            'password_must_change' => true,
            'created_by' => auth()->id(),
        ]);

        // Attribuer le rôle parent via Spatie
        $user->assignRole('parent');

        // Créer le parent
        $parent = ParentModel::create([
            'matricule_parent' => ParentModel::generateMatricule(),
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
            'profession' => $validated['profession'] ?? null,
            'statut' => $validated['statut'],
            'user_id' => $user->id,
        ]);

        // Journalisation
        Log::info('Parent créé', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'user_id' => $user->id,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent créé avec succès. Matricule : ' . $parent->matricule_parent . ' | Mot de passe temporaire : password');
    }

    /**
     * Afficher les détails d'un parent
     */
    public function show(ParentModel $parent)
    {
        $parent->load([
            'user',
            'students' => function ($query) {
                $query->with(['latestRegistration.classroom', 'latestRegistration.schoolYear']);
            },
        ]);

        // Charger les informations détaillées pour chaque enfant
        foreach ($parent->students as $student) {
            $student->load([
                'registrations' => function ($query) {
                    $query->with(['classroom', 'schoolYear', 'payments'])->latest();
                },
            ]);
        }

        // Calculer les statistiques pour chaque enfant
        $studentsData = $parent->students->map(function ($student) {
            $currentRegistration = $student->registrations->first();
            $totalPaid = $student->registrations->flatMap->payments->sum('amount');
            $remainingBalance = $student->registrations->flatMap->payments->sum('remaining_balance');
            
            // Dernières absences (simulé - à adapter selon votre logique métier)
            $recentAbsences = []; // À implémenter selon vos besoins
            
            // Dernières notes validées
            $recentNotes = Note::whereHas('registration', function ($query) use ($student) {
                $query->where('user_id', $student->id);
            })->latest()->take(5)->get();

            return [
                'student' => $student,
                'currentRegistration' => $currentRegistration,
                'totalPaid' => $totalPaid,
                'remainingBalance' => $remainingBalance,
                'recentAbsences' => $recentAbsences,
                'recentNotes' => $recentNotes,
            ];
        });

        return view('parents.show', [
            'parent' => $parent,
            'studentsData' => $studentsData,
        ]);
    }

    /**
     * Afficher le formulaire d'édition d'un parent
     */
    public function edit(ParentModel $parent)
    {
        $parent->load('user');

        return view('parents.edit', [
            'parent' => $parent,
        ]);
    }

    /**
     * Mettre à jour les informations d'un parent
     */
    public function update(Request $request, ParentModel $parent)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('parents')->ignore($parent->id)],
            'telephone' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string'],
            'profession' => ['nullable', 'string', 'max:255'],
            'statut' => ['required', Rule::in(['actif', 'en_attente_activation', 'archive'])],
        ]);

        // Mettre à jour le parent
        $parent->update($validated);

        // Mettre à jour l'utilisateur associé si l'email change
        if ($parent->user && $parent->user->email !== $validated['email']) {
            $parent->user->update([
                'email' => $validated['email'],
                'name' => $validated['nom'] . ' ' . $validated['prenom'],
                'is_active' => $validated['statut'] === 'actif',
            ]);
        }

        // Journalisation
        Log::info('Parent mis à jour', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('parents.show', $parent)
            ->with('success', 'Informations du parent mises à jour avec succès.');
    }

    /**
     * Archiver un parent (soft delete)
     */
    public function archive(ParentModel $parent)
    {
        $parent->update(['statut' => 'archive']);
        
        if ($parent->user) {
            $parent->user->update(['is_active' => false]);
        }

        // Journalisation
        Log::info('Parent archivé', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'archived_by' => auth()->id(),
        ]);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent archivé avec succès.');
    }

    /**
     * Restaurer un parent archivé
     */
    public function restore($id)
    {
        $parent = ParentModel::withTrashed()->findOrFail($id);
        
        $parent->restore();
        $parent->update(['statut' => 'actif']);
        
        if ($parent->user) {
            $parent->user->update(['is_active' => true]);
        }

        // Journalisation
        Log::info('Parent restauré', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'restored_by' => auth()->id(),
        ]);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent restauré avec succès.');
    }

    /**
     * Supprimer définitivement un parent
     */
    public function destroy(ParentModel $parent)
    {
        // Supprimer d'abord les relations avec les élèves
        $parent->students()->detach();

        // Supprimer l'utilisateur associé
        if ($parent->user) {
            $parent->user->delete();
        }

        // Supprimer le parent
        $parent->delete();

        // Journalisation
        Log::info('Parent supprimé', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'deleted_by' => auth()->id(),
        ]);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent supprimé avec succès.');
    }

    /**
     * Associer un élève à un parent
     */
    public function attachStudent(Request $request, ParentModel $parent)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'lien_parente' => ['required', Rule::in(['Pere', 'Mere', 'Tuteur', 'Tutrice', 'Autre'])],
            'est_responsable_financier' => ['boolean'],
            'est_contact_urgence' => ['boolean'],
        ]);

        $student = User::where('id', $validated['user_id'])->where('role', 'eleve')->firstOrFail();

        // Vérifier si la relation existe déjà
        if ($parent->students()->where('user_id', $student->id)->exists()) {
            return back()->withErrors(['user_id' => 'Cet élève est déjà associé à ce parent.'])->withInput();
        }

        // Attacher l'élève au parent
        $parent->students()->attach($student->id, [
            'lien_parente' => $validated['lien_parente'],
            'est_responsable_financier' => $validated['est_responsable_financier'] ?? false,
            'est_contact_urgence' => $validated['est_contact_urgence'] ?? false,
        ]);

        // Journalisation
        Log::info('Élève associé au parent', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'lien_parente' => $validated['lien_parente'],
            'attached_by' => auth()->id(),
        ]);

        return back()->with('success', 'Élève associé au parent avec succès.');
    }

    /**
     * Dissocier un élève d'un parent
     */
    public function detachStudent(ParentModel $parent, User $student)
    {
        if ($student->role !== 'eleve') {
            return back()->withErrors(['user' => 'Cet utilisateur n\'est pas un élève.']);
        }

        $parent->students()->detach($student->id);

        // Journalisation
        Log::info('Élève dissocié du parent', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'detached_by' => auth()->id(),
        ]);

        return back()->with('success', 'Élève dissocié du parent avec succès.');
    }

    /**
     * Réinitialiser le mot de passe du compte parent
     */
    public function resetPassword(ParentModel $parent)
    {
        if ($parent->user) {
            $parent->user->update([
                'password' => Hash::make('password'),
                'password_must_change' => true,
            ]);

            // Journalisation
            Log::info('Mot de passe parent réinitialisé', [
                'parent_id' => $parent->id,
                'matricule_parent' => $parent->matricule_parent,
                'reset_by' => auth()->id(),
            ]);

            return back()->with('success', 'Mot de passe réinitialisé. Nouveau mot de passe temporaire : password');
        }

        return back()->withErrors(['user' => 'Aucun compte utilisateur associé à ce parent.']);
    }
}
