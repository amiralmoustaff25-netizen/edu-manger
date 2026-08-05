<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParentRequest;
use App\Http\Requests\UpdateParentRequest;
use App\Models\Note;
use App\Models\ParentModel;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ParentController extends Controller
{
    /**
     * Liste paginée des parents avec recherche et filtrage
     */
    public function index(Request $request): View
    {
        $this->authorize('voir-parents');

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
    public function create(): View
    {
        $this->authorize('creer-parent');

        return view('parents.create', [
            'parent' => new ParentModel(['statut' => 'en_attente_activation']),
        ]);
    }

    /**
     * Créer un nouveau parent avec son compte utilisateur
     */
    public function store(StoreParentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $parent = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['nom'].' '.$validated['prenom'],
                'email' => $validated['email'],
                'password' => Hash::make('password'),
                'matricule' => User::generateMatricule('parent'),
                'role' => 'parent',
                'is_active' => $validated['statut'] === 'actif',
                'password_must_change' => true,
                'created_by' => auth()->id(),
            ]);

            $user->assignRole('parent');

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

            return $parent;
        });

        Log::info('Parent créé', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'user_id' => $parent->user_id,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent créé avec succès. Matricule : '.$parent->matricule_parent.' | Mot de passe temporaire : password');
    }

    /**
     * Afficher les détails d'un parent
     */
    public function show(ParentModel $parent, FeeService $feeService): View
    {
        $this->authorize('voir-detail-parent', $parent);

        $parent->load([
            'user',
            'students' => function ($query) {
                $query->with(['latestRegistration.classroom', 'latestRegistration.schoolYear']);
            },
        ]);

        foreach ($parent->students as $student) {
            $student->load([
                'registrations' => function ($query) {
                    $query->with(['classroom', 'schoolYear', 'payments'])->latest();
                },
            ]);
        }

        $studentsData = $parent->students->map(function ($student) use ($feeService) {
            $currentRegistration = $student->registrations->first();
            // Les paiements annulés sont exclus des totaux (traçabilité conservée dans audit_logs).
            $activePayments = $student->registrations->flatMap->payments->whereNull('cancelled_at');
            $totalPaid = $activePayments->sum('amount');
            // Le solde restant ne doit JAMAIS être obtenu en sommant la colonne
            // `remaining_balance` de plusieurs paiements : cette colonne n'est qu'un instantané
            // au moment de CE paiement, pas un solde cumulable. Seul FeeService::getFinancialSituation()
            // (basé sur les frais réellement dus pour l'inscription courante) donne le solde exact.
            $remainingBalance = $currentRegistration
                ? $feeService->getFinancialSituation($currentRegistration)['remaining']
                : 0;
            $recentAbsences = [];
            $recentNotes = Note::where('user_id', $student->id)->latest()->take(5)->get();

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
    public function edit(ParentModel $parent): View
    {
        $this->authorize('modifier-parent', $parent);

        $parent->load('user');

        return view('parents.edit', [
            'parent' => $parent,
        ]);
    }

    /**
     * Mettre à jour les informations d'un parent
     */
    public function update(UpdateParentRequest $request, ParentModel $parent): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $parent) {
            $parent->update($validated);

            if ($parent->user) {
                $parent->user->update([
                    'email' => $validated['email'],
                    'name' => $validated['nom'].' '.$validated['prenom'],
                    'is_active' => $validated['statut'] === 'actif',
                ]);
            }
        });

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
    public function archive(ParentModel $parent): RedirectResponse
    {
        $this->authorize('archiver-parent', $parent);

        DB::transaction(function () use ($parent) {
            $parent->update(['statut' => 'archive']);

            if ($parent->user) {
                $parent->user->update(['is_active' => false]);
            }

            $parent->delete();
        });

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
    public function restore($id): RedirectResponse
    {
        $parent = ParentModel::withTrashed()->findOrFail($id);

        $this->authorize('restaurer-parent', $parent);

        DB::transaction(function () use ($parent) {
            $parent->restore();
            $parent->update(['statut' => 'actif']);

            if ($parent->user) {
                $parent->user->update(['is_active' => true]);
            }
        });

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
    public function destroy(ParentModel $parent): RedirectResponse
    {
        $this->authorize('supprimer-parent', $parent);

        DB::transaction(function () use ($parent) {
            $parent->students()->detach();

            if ($parent->user) {
                $parent->user->forceDelete();
            }

            $parent->forceDelete();
        });

        Log::info('Parent supprimé définitivement', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'deleted_by' => auth()->id(),
        ]);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent supprimé définitivement avec succès.');
    }

    /**
     * Associer un élève à un parent
     */
    public function attachStudent(Request $request, ParentModel $parent): RedirectResponse
    {
        $this->authorize('associer-eleve-parent', $parent);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'lien_parente' => ['required', Rule::in(['Pere', 'Mere', 'Tuteur', 'Tutrice', 'Autre'])],
            'est_responsable_financier' => ['boolean'],
            'est_contact_urgence' => ['boolean'],
        ]);

        $student = User::findOrFail($validated['user_id']);

        if (! $student->hasRole('eleve')) {
            return back()->withErrors(['user_id' => 'Cet utilisateur n\'est pas un élève.'])->withInput();
        }

        if ($parent->students()->where('user_id', $student->id)->exists()) {
            return back()->withErrors(['user_id' => 'Cet élève est déjà associé à ce parent.'])->withInput();
        }

        $parent->students()->attach($student->id, [
            'lien_parente' => $validated['lien_parente'],
            'est_responsable_financier' => $validated['est_responsable_financier'] ?? false,
            'est_contact_urgence' => $validated['est_contact_urgence'] ?? false,
        ]);

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
    public function detachStudent(ParentModel $parent, User $student): RedirectResponse
    {
        $this->authorize('dissocier-eleve-parent', $parent);

        if (! $student->hasRole('eleve')) {
            return back()->withErrors(['user' => 'Cet utilisateur n\'est pas un élève.']);
        }

        $parent->students()->detach($student->id);

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
    public function resetPassword(ParentModel $parent): RedirectResponse
    {
        $this->authorize('reinitialiser-mot-de-passe-parent', $parent);

        if (! $parent->user) {
            return back()->withErrors(['user' => 'Aucun compte utilisateur associé à ce parent.']);
        }

        $parent->user->update([
            'password' => Hash::make('password'),
            'password_must_change' => true,
        ]);

        Log::info('Mot de passe parent réinitialisé', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'reset_by' => auth()->id(),
        ]);

        return back()->with('success', 'Mot de passe réinitialisé. Nouveau mot de passe temporaire : password');
    }
}
