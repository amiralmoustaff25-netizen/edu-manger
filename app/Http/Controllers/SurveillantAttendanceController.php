<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveillantAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $activeYear = SchoolYear::where('is_active', true)->first();
        $classrooms = Classroom::with('schoolYear')
            ->when($activeYear, fn ($query) => $query->where('school_year_id', $activeYear->id))
            ->orderBy('name')
            ->get();

        return view('surveillant.attendances.index', compact('classrooms'));
    }

    public function class(Request $request, Classroom $classroom): View
    {
        $date = $request->date('date') ?? today();
        $dateString = $date->toDateString();

        $students = Registration::where('classroom_id', $classroom->id)
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->sortBy('name');

        $attendances = Attendance::where('classroom_id', $classroom->id)
            ->whereDate('date', $dateString)
            ->get()
            ->keyBy('user_id');

        $recentHistory = Attendance::where('classroom_id', $classroom->id)
            ->whereBetween('date', [$date->copy()->subDays(6)->toDateString(), $dateString])
            ->with('student')
            ->orderByDesc('date')
            ->get()
            ->groupBy(fn ($a) => $a->date->format('d/m/Y'));

        return view('surveillant.attendances.class', compact(
            'classroom',
            'date',
            'dateString',
            'students',
            'attendances',
            'recentHistory'
        ));
    }

    public function student(Request $request, User $student): View
    {
        $attendances = Attendance::where('user_id', $student->id)
            ->with('classroom')
            ->orderByDesc('date')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'present' => Attendance::where('user_id', $student->id)->where('status', 'present')->count(),
            'absent' => Attendance::where('user_id', $student->id)->where('status', 'absent')->count(),
            'late' => Attendance::where('user_id', $student->id)->where('status', 'late')->count(),
            'excused' => Attendance::where('user_id', $student->id)->where('status', 'excused')->count(),
        ];

        return view('surveillant.attendances.student', compact('student', 'attendances', 'stats'));
    }
}
