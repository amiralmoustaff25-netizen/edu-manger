<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ProgramAnnual;
use App\Models\SchoolYear;
use App\Models\TeachingSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveillantDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $activeYear = SchoolYear::where('is_active', true)->first();
        $today = now()->toDateString();

        $classrooms = Classroom::with('schoolYear')
            ->when($activeYear, fn ($query) => $query->where('school_year_id', $activeYear->id))
            ->orderBy('name')
            ->get();

        $todaysAttendances = Attendance::whereDate('date', $today)->count();
        $todaysSessions = TeachingSession::whereDate('taught_on', $today)->count();
        $absentStudents = Attendance::whereDate('date', $today)->where('status', 'absent')->count();

        $pendingPrograms = 0;
        if (auth()->user()->can('voir-programmes')) {
            $pendingPrograms = ProgramAnnual::where('status', 'submitted')->count();
        }

        return view('surveillant.dashboard', compact(
            'classrooms',
            'todaysAttendances',
            'todaysSessions',
            'absentStudents',
            'pendingPrograms',
            'activeYear'
        ));
    }
}
