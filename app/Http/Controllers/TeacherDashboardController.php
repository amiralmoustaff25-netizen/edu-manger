<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\ProgramAnnual;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        // Récupérer les classes liées au professeur avec les détails
        $classrooms = $teacherModel->classrooms()
            ->with(['schoolYear', 'registrations' => function ($query) {
                $query->where('status', 'active');
            }])
            ->withPivot('matiere_id', 'volume_horaire_hebdo', 'annee_scolaire')
            ->get();

        // Enrichir les classes avec les informations de progression des cours
        $classroomDetails = [];
        foreach ($classrooms as $classroom) {
            $matiere = $classroom->pivot->matiere_id ? Matiere::find($classroom->pivot->matiere_id) : null;
            
            // Récupérer les programmes pour cette classe et matière
            $programs = ProgramAnnual::where('classroom_id', $classroom->id)
                ->when($matiere, function ($query) use ($matiere) {
                    return $query->where('matiere_id', $matiere->id);
                })
                ->with('chapterCompletions')
                ->get();

            // Calculer la progression moyenne des programmes
            $totalProgress = 0;
            $programsCount = $programs->count();
            foreach ($programs as $program) {
                $totalChapters = $program->chapters->count();
                $completedChapters = $program->chapterCompletions->count();
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

            // Calculer la moyenne de la classe
            $classAverage = Note::where('classroom_id', $classroom->id)->avg('valeur') ?? 0;

            $classroomDetails[] = [
                'classroom' => $classroom,
                'matiere' => $matiere,
                'volume_horaire' => $classroom->pivot->volume_horaire_hebdo,
                'annee_scolaire' => $classroom->pivot->annee_scolaire,
                'total_students' => $totalStudents,
                'absent_today' => $absentToday,
                'attendance_rate' => round($attendanceRate, 1),
                'class_average' => round($classAverage, 2),
                'programs_count' => $programsCount,
                'average_progress' => $averageProgress,
            ];
        }

        // Statistiques globales
        $stats = [
            'total_classes' => $classrooms->count(),
            'total_students' => collect($classroomDetails)->sum('total_students'),
            'total_hours' => collect($classroomDetails)->sum('volume_horaire'),
            'average_attendance' => collect($classroomDetails)->avg('attendance_rate') ?? 100,
            'average_progress' => collect($classroomDetails)->avg('average_progress') ?? 0,
        ];

        return view('teachers.dashboard', compact(
            'classroomDetails',
            'stats'
        ));
    }
}
