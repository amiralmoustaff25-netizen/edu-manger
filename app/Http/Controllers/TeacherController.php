<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\UserRoles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Teacher::class);

        $teachers = Teacher::query()
            // withTrashed() : sans ça, le filtre "Archivé" ne peut structurellement
            // jamais rien retourner (même bug déjà corrigé pour Utilisateurs/Élèves/
            // Parents). Le user lié est aussi chargé avec ses lignes soft-deleted,
            // sinon une ligne archivée afficherait un nom/email vides.
            ->withTrashed()
            ->with(['user' => fn ($query) => $query->withTrashed(), 'classrooms'])
            ->withCount('pedagogicalAssignments')
            ->withSum(['pedagogicalAssignments' => fn ($query) => $query->where('is_active', true)], 'volume_horaire_hebdo')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('matricule', 'like', "%{$search}%");
            })
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')->toString()))
            ->when($request->filled('matiere'), function ($query) use ($request) {
                // Le filtre "Classe / Matière" doit chercher dans les deux mécanismes
                // d'affectation : le pivot teacher_classroom (nom de classe) et les
                // PedagogicalAssignment actives (classe ET matière), seule source où la
                // matière est réellement rattachée à un professeur.
                $search = $request->string('matiere')->toString();
                $query->where(function ($query) use ($search) {
                    $query->whereHas('classrooms', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('pedagogicalAssignments', function ($q) use ($search) {
                            $q->where('is_active', true)
                                ->where(function ($q) use ($search) {
                                    $q->whereHas('classroom', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                                        ->orWhereHas('matiere', fn ($q) => $q->where('nom', 'like', "%{$search}%"));
                                });
                        });
                });
            })
            ->when($request->string('statut_compte')->toString() === 'archived', function ($query) {
                $query->whereNotNull('deleted_at');
            }, function ($query) {
                $query->whereNull('deleted_at');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('teachers.index', [
            'teachers' => $teachers,
            'statuts' => ['fonctionnaire', 'contractuel', 'vacataire'],
            'filters' => $request->only(['search', 'statut', 'matiere', 'statut_compte']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Teacher::class);

        return view('teachers.create', [
            'teacher' => new Teacher,
            'canViewRib' => $this->authorizeCanViewRib(),
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        Gate::authorize('create', Teacher::class);

        $validated = $request->validated();
        $temporaryPassword = Str::password(12);

        $teacher = DB::transaction(function () use ($validated, $temporaryPassword) {
            // Le compte utilisateur et la fiche enseignant d'un même professeur doivent
            // partager le même matricule (c'est ce qui les identifie comme la même
            // personne dans les écrans "Utilisateurs" et "Affectations pédagogiques") —
            // générer deux matricules indépendamment (un par User::generateMatricule(),
            // un par Teacher::generateMatricule()) les a fait diverger silencieusement
            // pour tous les professeurs créés depuis un ancien enregistrement legacy.
            $matricule = Teacher::generateMatricule();

            $user = User::create([
                'name' => $validated['nom'].' '.$validated['prenom'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'],
                'password' => Hash::make($temporaryPassword),
                'matricule' => $matricule,
                'telephone' => $validated['telephone'] ?? null,
                'date_naissance' => $validated['date_naissance'],
                'specialite' => implode(', ', $validated['specialites']),
                'created_by' => auth()->id(),
                'is_active' => true,
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
                'rib' => ($validated['rib'] ?? '') !== '' ? $validated['rib'] : null,
                'nombre_heures_semaine' => $validated['nombre_heures_semaine'] ?? 0,
                'created_by' => auth()->id(),
            ]);
        });

        // SEC-04 : la création/modification/suppression d'un compte professeur
        // n'était pas journalisée (contrairement au paiement/aux notes).
        app(AuditLogService::class)->log('created', Teacher::class, $teacher->id, null, ['matricule' => $teacher->matricule]);

        return redirect()->route('teachers.index')
            ->with('success', 'Professeur créé avec succès. Matricule : '.$teacher->matricule.'.')
            ->with('temp_password', $temporaryPassword)
            ->with('warning', 'Ce mot de passe temporaire est affiché une seule fois. Notez-le avant de quitter la page.');
    }

    public function show(Teacher $teacher): View
    {
        Gate::authorize('view', $teacher);

        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();
        $teacher->load(['user', 'classrooms.schoolYear']);
        $assignments = $teacher->pedagogicalAssignments()
            ->with(['classroom', 'matiere', 'schoolYear'])
            ->where('is_active', true)
            ->withCount('teachingSessions')
            ->withSum('teachingSessions as total_taught_hours', 'duration_hours')
            ->withSum(['teachingSessions as weekly_taught_hours' => fn ($query) => $query->whereBetween('taught_on', [$weekStart, $weekEnd])], 'duration_hours')
            ->orderBy('classroom_id')
            ->get()
            ->each(function ($assignment) {
                $assignment->total_taught_hours = (float) ($assignment->total_taught_hours ?? 0);
                $assignment->weekly_taught_hours = (float) ($assignment->weekly_taught_hours ?? 0);
                $assignment->remaining_weekly_hours = max(0, (float) $assignment->volume_horaire_hebdo - $assignment->weekly_taught_hours);
                $assignment->weekly_progress = $assignment->volume_horaire_hebdo > 0
                    ? min(100, round($assignment->weekly_taught_hours / $assignment->volume_horaire_hebdo * 100))
                    : 0;
            });

        return view('teachers.show', [
            'teacher' => $teacher,
            'assignments' => $assignments,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'canViewRib' => $this->authorizeCanViewRib(),
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        Gate::authorize('update', $teacher);

        $teacher->load(['user', 'classrooms']);

        return view('teachers.edit', [
            'teacher' => $teacher,
            'canViewRib' => $this->authorizeCanViewRib(),
        ]);
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        Gate::authorize('update', $teacher);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $teacher) {
            $teacher->user->update([
                'name' => $validated['nom'].' '.$validated['prenom'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? null,
                'date_naissance' => $validated['date_naissance'],
                'specialite' => implode(', ', $validated['specialites']),
            ]);

            $teacherData = [
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
            ];

            // Le champ RIB du formulaire n'est jamais pré-rempli (volontairement, pour ne
            // pas exposer le clair) : un formulaire soumis sans y retoucher envoie donc
            // toujours '' , qui écraserait silencieusement le RIB existant si on l'incluait
            // tel quel. On ne touche à 'rib' que si une nouvelle valeur a été saisie.
            if (($validated['rib'] ?? '') !== '') {
                $teacherData['rib'] = $validated['rib'];
            }

            $teacher->update($teacherData);
        });

        app(AuditLogService::class)->log('updated', Teacher::class, $teacher->id, null, ['matricule' => $teacher->matricule]);

        return redirect()->route('teachers.show', $teacher)->with('success', 'Professeur mis à jour avec succès.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        Gate::authorize('delete', $teacher);

        $blockingReason = UserRoles::activeBusinessLinkBlockingRoleChange($teacher->user);

        if ($blockingReason) {
            return back()->withErrors(['teacher' => $blockingReason]);
        }

        DB::transaction(function () use ($teacher) {
            $teacher->user->update(['is_active' => false]);
            $teacher->user->delete();
            $teacher->delete();
        });

        app(AuditLogService::class)->log('archived', Teacher::class, $teacher->id, null, ['matricule' => $teacher->matricule]);

        return redirect()->route('teachers.index')->with('success', 'Professeur désactivé et archivé.');
    }

    public function restore(int $id): RedirectResponse
    {
        $teacher = Teacher::withTrashed()->findOrFail($id);

        Gate::authorize('delete', $teacher);

        DB::transaction(function () use ($teacher) {
            $teacher->restore();

            $user = $teacher->user()->withTrashed()->first();
            if ($user) {
                $user->restore();
                $user->update(['is_active' => true]);
            }
        });

        app(AuditLogService::class)->log('restored', Teacher::class, $teacher->id, null, ['matricule' => $teacher->matricule]);

        return redirect()->route('teachers.index')->with('success', 'Professeur restauré.');
    }

    public function exportPdf(Request $request)
    {
        Gate::authorize('viewAny', Teacher::class);

        $teachers = Teacher::with('user')->latest()->get();
        $pdf = Pdf::loadView('teachers.pdf', [
            'teachers' => $teachers,
            'date' => now()->format('d/m/Y'),
            'ecole' => config('app.name'),
        ]);

        return $pdf->download('liste-professeurs-'.now()->format('Ymd').'.pdf');
    }

    public function exportCsv(): Response
    {
        Gate::authorize('viewAny', Teacher::class);

        $teachers = Teacher::with('user')->get();

        $csv = fopen('php://memory', 'r+');
        fputcsv($csv, [
            'Matricule',
            'Nom complet',
            'Email',
            'Statut',
            'Ancienneté',
            'Heures/semaine',
        ]);

        foreach ($teachers as $teacher) {
            fputcsv($csv, [
                $teacher->matricule,
                $teacher->user->name,
                $teacher->user->email,
                ucfirst($teacher->statut),
                $teacher->anciennete(),
                $teacher->volume_horaire_actuel,
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="professeurs.csv"',
        ]);
    }

    private function authorizeCanViewRib(): bool
    {
        // TeacherPolicy::manageRib() centralise déjà cette règle mais n'était jamais
        // invoquée : ce contrôleur la réimplémentait indépendamment en dur.
        return Gate::allows('manageRib', Teacher::class);
    }
}
