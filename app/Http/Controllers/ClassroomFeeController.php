<?php

namespace App\Http\Controllers;

use App\Models\ClassroomFee;
use App\Models\Classroom;
use App\Models\FeeType;
use App\Models\SchoolYear;
use App\Services\SchoolYearGuardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomFeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('voir-comptabilite');

        $query = ClassroomFee::with(['classroom', 'feeType', 'schoolYear'])->current();

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

    public function store(Request $request, SchoolYearGuardService $schoolYearGuard)
    {
        $this->authorize('creer-frais-classe');

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'school_year_id' => 'required|exists:school_years,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $schoolYearGuard->assertNotLocked(SchoolYear::find($validated['school_year_id']));

        $existing = ClassroomFee::current()
            ->where('classroom_id', $validated['classroom_id'])
            ->where('fee_type_id', $validated['fee_type_id'])
            ->where('school_year_id', $validated['school_year_id'])
            ->first();

        if ($existing) {
            return back()->withInput()->with('error', 'Un tarif actif existe déjà pour cette combinaison classe / type de frais / année scolaire. Modifiez-le plutôt.');
        }

        ClassroomFee::create([
            ...$validated,
            'version' => 1,
            'is_current' => true,
            'created_by' => $request->user()->id,
        ]);

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

    public function update(Request $request, ClassroomFee $classroomFee, SchoolYearGuardService $schoolYearGuard)
    {
        $this->authorize('modifier-frais-classe', $classroomFee);

        $schoolYearGuard->assertNotLocked($classroomFee->schoolYear);

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'school_year_id' => 'required|exists:school_years,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $schoolYearGuard->assertNotLocked(SchoolYear::find($validated['school_year_id']));

        // Versionnement : on ne modifie jamais un tarif en place, on crée une
        // nouvelle version et on archive l'ancienne pour garder l'historique complet.
        $newVersion = ClassroomFee::create([
            ...$validated,
            'version' => $classroomFee->version + 1,
            'is_current' => true,
            'previous_id' => $classroomFee->id,
            'created_by' => $request->user()->id,
        ]);

        $classroomFee->update(['is_current' => false]);

        return redirect()->route('classroom-fees.index')
            ->with('success', "Nouvelle version (v{$newVersion->version}) du tarif enregistrée avec succès.");
    }

    public function destroy(Request $request, ClassroomFee $classroomFee, SchoolYearGuardService $schoolYearGuard)
    {
        $this->authorize('supprimer-frais-classe', $classroomFee);

        $schoolYearGuard->assertNotLocked($classroomFee->schoolYear);

        $classroomFee->update(['deleted_by' => $request->user()->id]);
        $classroomFee->delete();

        return redirect()->route('classroom-fees.index')
            ->with('success', 'Frais de classe supprimés avec succès.');
    }

    public function history(ClassroomFee $classroomFee): View
    {
        $this->authorize('voir-comptabilite');

        $classroomFee->load(['classroom', 'feeType', 'schoolYear']);

        // Remonte jusqu'à la première version, puis liste toute la chaîne.
        $root = $classroomFee;
        while ($root->previousVersion) {
            $root = $root->previousVersion;
        }

        $versions = collect([$root]);
        $current = $root;
        while ($next = $current->nextVersions()->withTrashed()->first()) {
            $versions->push($next);
            $current = $next;
        }

        $versions = $versions->sortByDesc('version')->values()->load('createdBy', 'deletedBy');

        return view('accounting.classroom-fees.history', compact('classroomFee', 'versions'));
    }
}
