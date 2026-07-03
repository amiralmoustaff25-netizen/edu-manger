<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\User;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function create()
    {
        $this->authorize('creer-parent');

        return view('parents.create', [
            'parent' => new ParentModel(['statut' => 'en_attente_activation']),
        ]);
    }

    /**
     * Créer un nouveau parent avec son compte utilisateur
     */
    public function store(Request $request)
    {
        $this->authorize('creer-parent');

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

        $parent = DB::transaction(function () use ($validated) {
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

            return $parent;
        });

        // Journalisation
        Log::info('Parent créé', [
            'parent_id' => $parent->id,
            'matricule_parent' => $parent->matricule_parent,
            'user_id' => $parent->user_id,
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
        $this->authorize('voir-detail-parent', $parent);

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

            // Dernières absences (simulé - à