<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Teacher;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Teacher::class);

        $teachers = Teacher::query()
            ->with(['user', 'classrooms', 'classrooms.pivot'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('matricule', 'like', "%{$search}%");
            })
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')->toString()))
            ->when($request->filled('matiere'), function ($query) use ($request) {
                $matiere = $request->string('matiere')->toString();
                $query->whereHas('classrooms', function ($query) use ($matiere) {
                    $query->where('name', 'like', "%{$matiere}%");
                });
            })
            ->when($request->filled('anciennete'), function ($query) use ($request) {
                if (preg_match('/^(\d+)$/', $request->string('anciennete')->toString(), $matches)) {
                    $query->whereDate('date_recrutement', '<=', now()->subYears((int) $matches[1]));
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('teachers.index', [
            'teachers' => $teachers,
            'statuts' => ['fonctionnaire', 'contractuel', 'vacataire'],
            'filters' => $request->only(['search', 'statut', 'matiere', 'anciennete']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Teacher::class);

        return view('teachers.create', [
            'teacher' => new Teacher(),
            'classrooms' => Classroom::all(),
            'matieres' => Matiere::all(),
            'canViewRib' => false,
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        Gate::authorize('create', Teacher::class);

        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['nom'].' '.$validated['prenom'],
                'email' => $validated['email'],
                'password' => bcrypt('password'),
                'matricule' => User::generateMatricule('professeur'),
                'role' => 'professeur',
                'telephone' => $validated['telephone'] ?? null,
                'date_naissance' => $validated['date_naissance'],
                'specialite' => implode(', ', $validated['specialites']),
                'created_by' => auth()->id(),
                'is_active' => true,
                'password_must_change' => true,
            ]);

            $user->assignRole('professeur');

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'matricule' => Teacher::generateMatricule(),
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
                'rib' => $validated['rib'] ?? null,
                'nombre_heures_semaine' => $validated['nombre_heures_semaine'] ?? 0,
                'created_by' => auth()->id(),
            ]);

            $classrooms = collect($validated['classrooms'] ?? [])
                ->filter(fn ($classroomData) => ! empty($classroomData['classroom_id']));

            foreach ($classrooms as $classroomData) {
                $teacher->classrooms()->attach($classroomData['classroom_id'], [
                    'annee_scolaire' => now()->year.'-'.(now()->year + 1),
                    'matiere_id' => $classroomData['matiere_id'] ?? null,
                    'volume_horaire_hebdo' => $classroomData['volume_horaire_hebdo'] ?? 0,
                ]);
            }
        });

        return redirect()->route('teachers.index')->with('success', 'Professeur créé avec succès.');
    }

    public function show(Teacher $teacher): View
    {
        Gate::authorize('view', $teacher);

        $teacher->load(['user', 'classrooms', 'classrooms.pivot', 'classrooms.schoolYear']);

        return view('teachers.show', [
            'teacher' => $teacher,
            'canViewRib' => $this->authorizeCanViewRib(),
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        Gate::authorize('update', $teacher);

        $teacher->load(['user', 'classrooms', 'classrooms.pivot']);

        return view('teachers.edit', [
            'teacher' => $teacher,
            'classrooms' => Classroom::all(),
            'matieres' => Matiere::all(),
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
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? null,
                'date_naissance' => $validated['date_naissance'],
                'specialite' => implode(', ', $validated['specialites']),
            ]);

            $teacher->update([
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
                'rib' => $validated['rib'] ?? null,
                'nombre_heures_semaine' => $validated['nombre_heures_semaine'] ?? 0,
            ]);

            $teacher->classrooms()->sync([]);

            $classrooms = collect($validated['classrooms'] ?? [])
                ->filter(fn ($classroomData) => ! empty($classroomData['classroom_id']));

            if ($classrooms->isNotEmpty()) {
                foreach ($classrooms as $classroomData) {
                    $teacher->classrooms()->attach($classroomData['classroom_id'], [
                        'annee_scolaire' => now()->year.'-'.(now()->year + 1),
                        'matiere_id' => $classroomData['matiere_id'] ?? null,
                        'volume_horaire_hebdo' => $classroomData['volume_horaire_hebdo'] ?? 0,
                    ]);
                }
            }
        });

        return redirect()->route('teachers.show', $teacher)->with('success', 'Professeur mis à jour avec succès.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        Gate::authorize('delete', $teacher);

        $teacher->delete();

        return redirect()->route('teachers.index')->with('success', 'Professeur supprimé avec succès.');
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
        return auth()->user()->hasRole(['super-admin', 'admin']);
    }
}
