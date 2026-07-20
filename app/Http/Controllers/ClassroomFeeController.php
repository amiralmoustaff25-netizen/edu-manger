<?php

namespace App\Http\Controllers;

use App\Models\ClassroomFee;
use App\Models\Classroom;
use App\Models\FeeType;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomFeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('voir-comptabilite');

        $query = ClassroomFee::with(['classroom', 'feeType', 'schoolYear']);

        // Filtres
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->string('classroom_id'));
        }

        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->string('fee_type_id'));
        }

        if ($request->filled('school_year_id')) {
            $query->where('school_year_id', $request->string('school_year_id'));
        }

        $classroomFees = $query->latest()->paginate(15)->withQueryString();
        $classrooms = Classroom::all();
        $feeTypes = FeeType::all();
        $schoolYears = SchoolYear::all();

        return view('accounting.classroom-fees.index', compact('classroomFees', 'classrooms', 'feeTypes', 'schoolYears'));
    }

    public function create(): View
    {
        $this->authorize('creer-frais-classe');

        $classrooms = Classroom::all();
        $feeTypes = FeeType::all();
        $schoolYears = SchoolYear::all();

        return view('accounting.classroom-fees.create', compact('classrooms', 'feeTypes', 'schoolYears'));
    }

    public function store(Request $request)
    {
        $this->authorize('creer-frais-classe');

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'school_year_id' => 'required|exists:school_years,id',
            'amount' => 'required|numeric|min:0',
        ]);

        ClassroomFee::create($validated);

        return redirect()->route('classroom-fees.index')
            ->with('success', 'Frais de classe créés avec succès.');
    }

    public function edit(ClassroomFee $classroomFee): View
    {
        $this->authorize('modifier-frais-classe', $classroomFee);

        $classroomFee->load(['classroom', 'feeType', 'schoolYear']);
        $classrooms = Classroom::all();
        $feeTypes = FeeType::all();
        $schoolYears = SchoolYear::all();

        return view('accounting.classroom-fees.edit', compact('classroomFee', 'classrooms', 'feeTypes', 'schoolYears'));
    }

    public function update(Request $request, ClassroomFee $classroomFee)
    {
        $this->authorize('modifier-frais-classe', $classroomFee);

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'school_year_id' => 'required|exists:school_years,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $classroomFee->update($validated);

        return redirect()->route('classroom-fees.index')
            ->with('success', 'Frais de classe modifiés avec succès.');
    }

    public function destroy(ClassroomFee $classroomFee)
    {
        $this->authorize('supprimer-frais-classe', $classroomFee);

        $classroomFee->delete();

        return redirect()->route('classroom-fees.index')
            ->with('success', 'Frais de classe supprimés avec succès.');
    }
}
