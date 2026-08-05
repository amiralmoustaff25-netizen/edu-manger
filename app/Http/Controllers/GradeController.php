<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Registration;
use App\Models\Teacher;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }
        
        $classrooms = $teacher->classrooms()
            ->with(['schoolYear'])
            ->get();

        $matieres = Matiere::all();

        return view('teachers.grades.index', compact('classrooms', 'matieres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'matiere_id' => 'required|exists:matieres,id',
            'type_evaluation' => 'required|string',
            'periode' => 'required|string',
            'grades' => 'required|array',
            'grades.*.user_id' => 'required|exists:users,id',
            'grades.*.valeur' => 'nullable|numeric|min:0|max:20',
            'grades.*.appreciation' => 'nullable|string',
        ]);

        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }
        
        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $matiere = Matiere::findOrFail($validated['matiere_id']);

        // Vérifier que le professeur est assigné à cette classe ET cette matière
        $isAssigned = $teacher->classrooms()
            ->where('classrooms.id', $classroom->id)
            ->wherePivot('matiere_id', $matiere->id)
            ->exists();

        if (!$isAssigned) {
            abort(403, 'Vous n\'êtes pas autorisé à saisir des notes pour cette matière dans cette classe.');
        }

        // Les notes déjà validées sont verrouillées : toute la saisie est rejetée pour éviter
        // une modification partielle silencieuse. Un privilégié doit d'abord les rouvrir (reopen()).
        $hasValidatedNotes = Note::where('classroom_id', $classroom->id)
            ->where('matiere_id', $matiere->id)
            ->where('type_evaluation', $validated['type_evaluation'])
            ->where('periode', $validated['periode'])
            ->whereIn('user_id', collect($validated['grades'])->pluck('user_id'))
            ->validated()
            ->exists();

        if ($hasValidatedNotes) {
            return back()->withInput()->with('error', 'Ces notes ont déjà été validées par l\'administration et sont verrouillées. Une réouverture privilégiée est nécessaire avant toute modification.');
        }

        $savedCount = 0;

        foreach ($validated['grades'] as $gradeData) {
            // Ignorer les notes vides
            if (!isset($gradeData['valeur']) || $gradeData['valeur'] === '') {
                continue;
            }

            Note::updateOrCreate(
                [
                    'user_id' => $gradeData['user_id'],
                    'classroom_id' => $classroom->id,
                    'matiere_id' => $matiere->id,
                    'type_evaluation' => $validated['type_evaluation'],
                    'periode' => $validated['periode'],
                ],
                [
                    'valeur' => $gradeData['valeur'],
                    'appreciation' => $gradeData['appreciation'] ?? null,
                ]
            );

            $savedCount++;
        }

        return redirect()
            ->route('professeur.notes.index', ['classroom_id' => $classroom->id, 'matiere_id' => $matiere->id])
            ->with('success', "{$savedCount} note(s) enregistrée(s) avec succès.");
    }

    /**
     * Valider (verrouiller) un lot de notes pour une classe / matière / évaluation / période.
     * Une fois validées, ces notes ne peuvent plus être modifiées par le professeur.
     */
    public function validateNotes(Request $request)
    {
        $this->authorize('validateNotes', Note::class);

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'matiere_id' => 'required|exists:matieres,id',
            'type_evaluation' => 'required|string',
            'periode' => 'required|string',
        ]);

        $notes = Note::where($validated)->notValidated()->get();

        foreach ($notes as $note) {
            $note->validate(auth()->id());
        }

        app(AuditLogService::class)->log('validated', Note::class, null, null, $validated, $notes->count().' note(s) validée(s) et verrouillée(s)');

        return back()->with('success', $notes->count().' note(s) validée(s) et verrouillée(s) avec succès.');
    }

    /**
     * Rouvrir un lot de notes validées : action privilégiée réservée au super-admin.
     */
    public function reopenNotes(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'matiere_id' => 'required|exists:matieres,id',
            'type_evaluation' => 'required|string',
            'periode' => 'required|string',
        ]);

        $notes = Note::where($validated)->validated()->get();

        foreach ($notes as $note) {
            $this->authorize('reopen', $note);
            $note->reopen();
        }

        app(AuditLogService::class)->log('reopened', Note::class, null, null, $validated, $notes->count().' note(s) rouverte(s) pour modification');

        return back()->with('success', $notes->count().' note(s) rouverte(s) avec succès.');
    }
}
