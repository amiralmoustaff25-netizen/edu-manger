<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User;
use App\Services\GradeCalculationService;
use App\Services\SchoolYearContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BulletinController extends Controller
{
    protected GradeCalculationService $gradeService;

    public function __construct(GradeCalculationService $gradeService)
    {
        $this->gradeService = $gradeService;
    }

    /**
     * Afficher le bulletin d'un élève pour une période
     */
    public function show(Request $request, User $student, string $period = 'trimestre_1'): View|RedirectResponse
    {
        // Allow students to view their own bulletin even without the generer-bulletins permission.
        $user = $request->user();

        $isStudentOwner = $user->id === $student->id && $user->hasRole('eleve');

        // Allow a parent who is linked to this student to view the bulletin.
        $isParentOfStudent = false;
        if ($user->hasRole('parent') && method_exists($user, 'parentProfile')) {
            $parentProfile = $user->parentProfile();
            if ($parentProfile) {
                $isParentOfStudent = $parentProfile->whereHas('students', function ($q) use ($student) {
                    $q->where('users.id', $student->id);
                })->exists();
            }
        }

        if (! ($isStudentOwner || $isParentOfStudent)) {
            abort_unless($request->user()->can('generer-bulletins'), 403);
        }

        try {
            $bulletin = $this->gradeService->getBulletinData($student, $period);

            return view('bulletins.show', compact('bulletin', 'period'));
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Générer le PDF du bulletin d'un élève
     */
    public function generatePdf(Request $request, User $student, string $period = 'trimestre_1')
    {
        abort_unless($request->user()->can('generer-bulletins'), 403);

        try {
            $bulletin = $this->gradeService->getBulletinData($student, $period);

            $pdf = Pdf::loadView('bulletins.pdf', compact('bulletin', 'period'));

            return $pdf->download("bulletin_{$student->name}_{$period}.pdf");
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Générer les bulletins PDF pour toute une classe
     */
    public function generateClassPdf(Request $request, Classroom $classroom, string $period = 'trimestre_1')
    {
        abort_unless($request->user()->can('generer-bulletins'), 403);

        try {
            $bulletins = $this->gradeService->getClassBulletins($classroom, $period);

            $pdf = Pdf::loadView('bulletins.class-pdf', compact('bulletins', 'classroom', 'period'));

            return $pdf->download("bulletins_{$classroom->name}_{$period}.pdf");
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Afficher la sélection pour générer des bulletins
     */
    public function index(Request $request, SchoolYearContext $context): View
    {
        abort_unless($request->user()->can('generer-bulletins'), 403);

        $viewingYear = $context->current();

        $classrooms = Classroom::with('schoolYear')
            ->when($viewingYear, fn ($query) => $query->where('school_year_id', $viewingYear->id))
            ->orderBy('ordre')
            ->orderBy('name')
            ->get();

        return view('bulletins.index', compact('classrooms', 'viewingYear'));
    }
}
