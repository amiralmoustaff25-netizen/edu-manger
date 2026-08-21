<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use App\Services\GradeCalculationService;
use App\Support\AcademicPeriods;
use App\Support\EvaluationTypeScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord de consultation des notes pour les profils Élève et Parent —
 * lecture seule (aucune création/modification/suppression de note ici, réservée à
 * GradeController côté professeur). Réutilisé par les deux rôles : ParentPortalController
 * délègue ici pour un enfant, comme il le fait déjà vers StudentController::show()
 * pour la fiche élève (même convention).
 */
class StudentNotesController extends Controller
{
    public function __construct(private GradeCalculationService $gradeService)
    {
    }

    public function show(Request $request, ?User $student = null): View
    {
        $viewer = $request->user();

        if ($student) {
            abort_unless($this->parentCanView($viewer, $student), 403, "Vous n'êtes pas autorisé à consulter les notes de cet élève.");
        } else {
            abort_unless($viewer->hasRole('eleve'), 403);
            $student = $viewer;
        }

        $registration = $student->latestRegistration;
        $classroom = $registration?->classroom;
        $cycle = $classroom?->cycle;
        $schoolYearId = $registration?->school_year_id;

        $periods = [];
        foreach (AcademicPeriods::forCycle($cycle) as $code => $label) {
            $periods[$code] = $this->buildPeriodData($student, $classroom, $schoolYearId, $code, $label);
        }

        return view('students.notes', [
            'user' => $student,
            'periods' => $periods,
            'defaultPeriod' => AcademicPeriods::defaultFor($cycle),
        ]);
    }

    /**
     * @return array{label: string, subjects: array, general_average: ?float, color: ?array}
     */
    private function buildPeriodData($student, $classroom, ?int $schoolYearId, string $periodCode, string $periodLabel): array
    {
        $notes = Note::where('user_id', $student->id)
            ->where('periode', $periodCode)
            ->with('matiere')
            ->orderBy('matiere_id')
            ->orderBy('created_at')
            ->get();

        $subjects = [];
        foreach ($notes->groupBy('matiere_id') as $matiereNotes) {
            $matiere = $matiereNotes->first()->matiere;
            if (! $matiere) {
                continue;
            }

            $coefficient = $classroom ? $this->gradeService->resolveCoefficient($matiere, $classroom, $schoolYearId) : null;
            $isWeightedCycle = $classroom && in_array($classroom->cycle, ['college', 'lycee'], true);

            $subjectAverage = $isWeightedCycle
                ? $this->gradeService->calculateWeightedSubjectAverage($matiereNotes)
                : round($matiereNotes->avg('valeur'), 2);

            $subjects[] = [
                'matiere' => $matiere->nom,
                'coefficient' => $coefficient,
                'average' => $subjectAverage,
                'colorBand' => $subjectAverage !== null ? $this->gradeService->getPerformanceColorBand($subjectAverage) : null,
                'evaluations' => $matiereNotes->map(fn (Note $note) => [
                    'type' => EvaluationTypeScope::LABELS[$note->type_evaluation] ?? $note->type_evaluation,
                    'type_code' => $note->type_evaluation,
                    'evaluation_number' => $note->evaluation_number,
                    'date' => $note->created_at,
                    'valeur' => (float) $note->valeur,
                    'appreciation' => $note->appreciation,
                ])->values()->all(),
            ];
        }

        $generalAverage = null;
        $colorBand = null;
        if ($classroom && $notes->isNotEmpty()) {
            $generalAverage = $this->gradeService->getBulletinData($student, $periodCode)['general_average'];
            $colorBand = $this->gradeService->getPerformanceColorBand($generalAverage);
        }

        return [
            'label' => $periodLabel,
            'subjects' => $subjects,
            'general_average' => $generalAverage,
            'color' => $colorBand,
        ];
    }

    private function parentCanView(User $viewer, User $student): bool
    {
        if (! $viewer->hasRole('parent') || ! method_exists($viewer, 'parentProfile')) {
            return false;
        }

        $parentProfile = $viewer->parentProfile()->first();

        return $parentProfile && $parentProfile->students()->where('users.id', $student->id)->exists();
    }
}
