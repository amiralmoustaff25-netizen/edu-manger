<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SuperAdminProtectionService;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly SuperAdminProtectionService $superAdminProtection) {}

    public function index(Request $request): View
    {
        $this->authorize('voir-utilisateurs');

        $users = User::query()
            // withTrashed() : sans ça, le filtre "Archivés" ne peut structurellement
            // jamais rien retourner (même bug déjà corrigé pour le module Parents).
            ->withTrashed()
            // teacher/parentProfile : pour le lien "Voir le dossier" (fusion des anciennes
            // pages Professeurs/Parents & Tuteurs dans Utilisateurs), sans requête N+1 par ligne.
            ->with(['creator', 'roles', 'teacher', 'parentProfile'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('matricule', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->role($request->string('role')->toString()))
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->string('status')->toString();

                if ($status === 'active') {
                    $query->whereNull('deleted_at')->where('is_active', true);
                }

                if ($status === 'inactive') {
                    $query->whereNull('deleted_at')->where('is_active', false);
                }

                if ($status === 'archived') {
                    $query->whereNotNull('deleted_at');
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => UserRoles::ALL,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('creer-utilisateur');

        return view('users.create', [
            'user' => new User(['is_active' => true]),
            'roles' => UserRoles::CREATABLE_VIA_USER_FORM,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Combiner nom et prénom pour le champ name
        $validated['name'] = trim($validated['nom'].' '.$validated['prenom']);

        $role = $validated['role'];
        unset($validated['role']);

        $temporaryPassword = Str::password(12);
        $isActive = $request->boolean('is_active', true);

        if ($role === 'professeur') {
            return $this->storeProfesseur($validated, $temporaryPassword, $isActive);
        }

        $validated['matricule'] = User::generateMatricule($role);
        $validated['password'] = Hash::make($temporaryPassword);
        $validated['created_by'] = auth()->id();
        $validated['password_must_change'] = true;
        $validated['is_active'] = $isActive;

        $user = DB::transaction(function () use ($validated, $role) {
            $user = User::create($validated);
            $user->syncRoles([$role]);
            $user->syncPrimaryRoleColumn();

            return $user;
        });

        // SEC-04 : la création/modification/suppression d'un compte utilisateur
        // n'était pas journalisée (contrairement au paiement/aux notes).
        app(AuditLogService::class)->log('created', User::class, $user->id, null, ['matricule' => $user->matricule, 'role' => $role]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur créé. Matricule : '.$user->matricule.'.')
            ->with('temp_password', $temporaryPassword)
            ->with('warning', 'Ce mot de passe temporaire est affiché une seule fois. Notez-le avant de quitter la page.');
    }

    /**
     * Création d'un professeur depuis le formulaire Utilisateurs générique : même logique
     * que TeacherController::store() (compte + fiche métier créés ensemble), pour que
     * "Ajouter un utilisateur" reste l'unique point d'entrée quel que soit le rôle choisi.
     */
    private function storeProfesseur(array $validated, string $temporaryPassword, bool $isActive): RedirectResponse
    {
        $teacher = DB::transaction(function () use ($validated, $temporaryPassword, $isActive) {
            // Même matricule partagé entre le compte et la fiche enseignant que dans
            // TeacherController::store() — voir le commentaire là-bas.
            $matricule = Teacher::generateMatricule();

            $user = User::create([
                'name' => $validated['name'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'],
                'password' => Hash::make($temporaryPassword),
                'matricule' => $matricule,
                'telephone' => $validated['telephone'] ?? null,
                'date_naissance' => $validated['date_naissance'],
                'specialite' => implode(', ', $validated['specialites']),
                'created_by' => auth()->id(),
                'is_active' => $isActive,
                'password_must_change' => true,
            ]);

            $user->assignRole('professeur');
            $user->syncPrimaryRoleColumn();

            return Teacher::create([
                'user_id' => $user->id,
                'matricule' => $matricule,
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'sexe' => $validated['sexe'],
                'nationalite' => $validated['nationalite'],
                'diplomes' => $validated['diplomes'],
                'etablissements_formation' => $validated['etablissements_formation'],
                'statut' => $validated['statut'],
                'date_recrutement' => $validated['date_recrutement'],
                'specialites' => $validated['specialites'],
                'filiation' => $validated['filiation'],
                'contact_urgence_nom' => $validated['contact_urgence_nom'],
                'contact_urgence_tel' => $validated['contact_urgence_tel'],
                'nombre_heures_semaine' => $validated['nombre_heures_semaine'] ?? 0,
                'created_by' => auth()->id(),
            ]);
        });

        app(AuditLogService::class)->log('created', Teacher::class, $teacher->id, null, ['matricule' => $teacher->matricule]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Professeur créé avec succès. Matricule : '.$teacher->user->matricule.'.')
            ->with('temp_password', $temporaryPassword)
            ->with('warning', 'Ce mot de passe temporaire est affiché une seule fois. Notez-le avant de quitter la page.');
    }

    public function edit(User $user): View
    {
        $this->authorize('modifier-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        return view('users.edit', [
            'user' => $user,
            'roles' => UserRoles::editableRolesFor(auth()->user(), $user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $validated['name'] = trim($validated['nom'].' '.$validated['prenom']);
        $validated['is_active'] = $request->boolean('is_active');

        $role = $validated['role'];
        unset($validated['role']);

        $oldValues = $user->only(array_keys($validated));

        DB::transaction(function () use ($user, $validated, $role) {
            $user->update($validated);
            $user->syncRoles([$role]);
            $user->syncPrimaryRoleColumn();
        });

        app(AuditLogService::class)->log('updated', User::class, $user->id, $oldValues, $validated + ['role' => $role]);

        return redirect()->route('users.index')->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('supprimer-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $blockingReason = UserRoles::activeBusinessLinkBlockingRoleChange($user);

        if ($blockingReason) {
            return back()->withErrors(['user' => $blockingReason]);
        }

        // L'archivage effectif de l'email (contrainte UNIQUE en base) est géré au
        // niveau du modèle (voir User::booted()) : il s'applique quel que soit le
        // chemin de suppression, pas seulement celui-ci.
        $user->update(['is_active' => false]);
        $user->delete();

        app(AuditLogService::class)->log('archived', User::class, $user->id, null, ['matricule' => $user->matricule]);

        return redirect()->route('users.index')->with('success', 'Utilisateur désactivé et archivé.');
    }

    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('supprimer-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        DB::transaction(function () use ($user) {
            $user->restore();
            $user->update(['is_active' => true]);
        });

        app(AuditLogService::class)->log('restored', User::class, $user->id, null, ['matricule' => $user->matricule]);

        return redirect()->route('users.index')->with('success', 'Utilisateur restauré.');
    }

    public function toggle(User $user): RedirectResponse
    {
        $this->authorize('activer-desactiver-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        $wasActive = $user->is_active;
        $user->update(['is_active' => ! $user->is_active]);

        app(AuditLogService::class)->log('toggled_active', User::class, $user->id, ['is_active' => $wasActive], ['is_active' => $user->is_active]);

        return back()->with('success', $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('reinitialiser-mot-de-passe-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        $temporaryPassword = config('edu.default_reset_password');

        $user->update([
            'password' => Hash::make($temporaryPassword),
            'password_must_change' => true,
        ]);

        // SEC-04 : auparavant seulement un Log::info non structuré — désormais
        // dans le même journal d'audit consultable via l'écran Logs de connexion.
        app(AuditLogService::class)->log('password_reset_by_admin', User::class, $user->id, null, null, 'Mot de passe réinitialisé par '.auth()->user()?->name);

        return back()
            ->with('success', 'Mot de passe réinitialisé.')
            ->with('temp_password', $temporaryPassword)
            ->with('warning', 'Ce mot de passe temporaire est affiché une seule fois. Notez-le avant de quitter la page.');
    }
}
