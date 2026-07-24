<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User;
use App\Services\GradeCalculationService;
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
    public function show(User $student, string $period = 'trimestre_1'): View|RedirectResponse
    {
        $this->authorize('view', $student);

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
    public function generatePdf(User $student, string $period = 'trimestre_1')
    {
        $this->authorize('view', $student);

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
    public function generateClassPdf(Classroom $classroom, string $period = 'trimestre_1')
    {
        $this->authorize('viewAny', Classroom::class);

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
    public function index(): View
    {
        $this->authorize('viewAny', Classroom::class);

        $classrooms = Classroom::with('schoolYear')->get();

        return view('bulletins.index', compact('classrooms'));
    }
}
