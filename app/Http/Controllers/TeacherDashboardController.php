<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Note;
use App\Models\ProgramAnnual;
use App\Models\SchoolYear;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();

        abort_if(! $teacherModel, 403, 'Votre profil enseignant est incomplet. Veuillez contacter un administrateur.');

        $schoolYear = SchoolYear::getActive();

        // Les classes affectées au professeur proviennent des affectations pédagogiques
        // (PedagogicalAssignment), pas de l'ancienne table pivot teacher_classroom qui
        // n'est plus alimentée depuis que les affectations passent par le module
        // "Configuration pédagogique" — sans ce changement, une nouvelle affectation
        // n'apparaissait jamais côté professeur.
        $assignments = $teacherModel->pedagogicalAssignments()
            ->where('is_active', true)
            ->when($schoolYear, fn ($query) => $query->where('school_year_id', $schoolYear->id))
            ->with([
                'classroom.schoolYear',
                'classroom.registrations' => fn ($query) => $query->where('status', 'active'),
                'matiere',
                'schoolYear',
            ])
            ->get();

        // Enrichir chaque affectation (classe + matière) avec les informations de progression des cours
        $classroomDetails = [];
        foreach ($assignments as $assignment) {
            $classroom = $assignment->classroom;
            $matiere = $assignment->matiere;

            // Récupérer les programmes pour cette classe et matière
            $programs = ProgramAnnual::where('classroom_id', $classroom->id)
                ->when($matiere, function ($query) use ($matiere) {
                    return $query->where('subject_id', $matiere->id);
                })
                ->with('chapters.completions')
                ->get();

            // Calculer la progression moyenne des programmes
            $totalProgress = 0;
            $programsCount = $programs->count();
            foreach ($programs as $program) {
                $totalChapters = $program->chapters->count();
                $completedChapters = $program->chapters->sum(fn ($chapter) => $chapter->completions->isNotEmpty() ? 1 : 0);
                if ($totalChapters > 0) {
                    $totalProgress += ($completedChapters / $totalChapters) * 100;
                }
            }
            $averageProgress = $programsCount > 0 ? round($totalProgress / $programsCount, 1) : 0;

            // Calculer le taux d'absentéisme
            $totalStudents = $classroom->registrations->count();
            $absentToday = 0;
            $attendanceRate = 100;
            if ($totalStudents > 0) {
                $absentToday = Attendance::where('classroom_id', $classroom->id)
                    ->where('date', today())
                    ->where('status', 'absent')
                    ->count();
                $attendanceRate = (($totalStudents - $absentToday) / $totalStudents) * 100;
            }

            // Calculer la moyenne de la classe pour cette matière
            $classAverage = Note::where('classroom_id', $classroom->id)
                ->when($matiere, fn ($query) => $query->where('matiere_id', $matiere->id))
                ->avg('valeur') ?? 0;

            $classroomDetails[] = [
                'classroom' => $classroom,
                'matiere' => $matiere,
                'volume_horaire' => $assignment->volume_horaire_hebdo,
                'annee_scolaire' => $assignment->schoolYear->year_string ?? $classroom->schoolYear->year_string ?? null,
                'total_students' => $totalStudents,
                'absent_today' => $absentToday,
                'attendance_rate' => round($attendanceRate, 1),
                'class_average' => round($classAverage, 2),
                'programs_count' => $programsCount,
                'average_progress' => $averageProgress,
            ];
        }

        // Statistiques globales — sur classes uniques pour éviter de compter deux fois
        // les élèves/présences d'une classe où le professeur enseigne plusieurs matières.
        $uniqueClassrooms = $assignments->pluck('classroom')->unique('id')->values();
        $detailsByClassroom = collect($classroomDetails)->unique(fn ($detail) => $detail['classroom']->id)->values();

        $stats = [
            'total_classes' => $uniqueClassrooms->count(),
            'total_students' => $detailsByClassroom->sum('total_students'),
            'total_hours' => collect($classroomDetails)->sum('volume_horaire'),
            'average_attendance' => $detailsByClassroom->avg('attendance_rate') ?? 100,
            'average_progress' => collect($classroomDetails)->avg('average_progress') ?? 0,
        ];

        return view('teachers.dashboard', compact(
            'classroomDetails',
            'stats'
        ));
    }
}
