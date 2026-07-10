<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();
        
        // Récupérer les classes liées au professeur
        $classrooms = $teacher->classrooms()
            ->with(['schoolYear', 'registrations' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get();

        // Calculer le taux d'absentéisme des classes assignées
        $attendanceStats = [];
        foreach ($classrooms as $classroom) {
            $totalStudents = $classroom->registrations->count();
            if ($totalStudents > 0) {
                $absentToday = Attendance::where('classroom_id', $classroom->id)
                    ->where('date', today())
                    ->where('status', 'absent')
                    ->count();
                $attendanceRate = (($totalStudents - $absentToday) / $totalStudents) * 100;
            } else {
                $attendanceRate = 100;
            }

            $attendanceStats[$classroom->id] = [
                'total_students' => $totalStudents,
                'absent_today' => $absentToday ?? 0,
                'attendance_rate' => round($attendanceRate, 1),
            ];
        }

        // Récupérer le planning du jour (simulé - nécessiterait un modèle Schedule)
        $todaySchedule = collect(); // À implémenter avec le modèle Schedule

        // Statistiques globales
        $stats = [
            'total_classes' => $classrooms->count(),
            'total_students' => $classrooms->sum(function ($classroom) {
                return $classroom->registrations->count();
            }),
            'average_attendance' => $attendanceStats 
                ? collect($attendanceStats)->avg('attendance_rate') 
                : 100,
            'recent_grades' => Note::whereHas('classroom', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })->latest()->take(5)->get(),
        ];

        return view('teachers.dashboard', compact(
            'classrooms',
            'attendanceStats',
            'todaySchedule',
            'stats'
        ));
    }
}
