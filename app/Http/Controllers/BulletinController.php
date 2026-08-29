<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
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

        $isOwnBulletin = $this->isStudentOrParentOf($user, $student);

        if ($isOwnBulletin) {
            // Décision produit (2026-08-29) : le bulletin n'est visible côté élève/parent
            // qu'une fois les notes de la période validées par la direction (admin), pas
            // avant — jusqu'ici cette route l'exposait sans aucune condition, y compris en
            // pleine saisie par les professeurs. Ce n'est pas un refus d'accès (abort 403)
            // mais un état métier normal, comme les notes déjà validées dans
            // GradeController::store() — même traitement (redirection + message).
            if (! Note::isPeriodPublishedFor($student->id, $period)) {
                // route('dashboard') plutôt qu'une route spécifique élève/parent : ce
                // contrôleur est atteint par les deux rôles (et par le raccourci
                // ParentPortalController::childBulletins, qui redirige lui-même ici sur
                // "trimestre_1" par défaut — rediriger vers children.bulletins boucherait).
                return redirect()->route('dashboard')->with('error', "Le bulletin de cette période n'est pas encore disponible.");
            }
        } else {
            abort_unless($user->can('generer-bulletins'), 403);
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
        $user = $request->user();

        if ($this->isStudentOrParentOf($user, $student)) {
            // Même règle de publication que show() ci-dessus : sans cette branche, le
            // bouton "PDF" de la page "Mes bulletins" (students.bulletins) était
            // toujours rejeté par le abort_unless ci-dessous, qui exige la permission
            // generer-bulletins — qu'un élève/parent n'a jamais.
            if (! Note::isPeriodPublishedFor($student->id, $period)) {
                return redirect()->route('dashboard')->with('error', "Le bulletin de cette période n'est pas encore disponible.");
            }
        } else {
            abort_unless($user->can('generer-bulletins'), 403);
            $this->ensureTeacherAssignedToStudent($user, $student);
        }

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
        $user = $request->user();
        abort_unless($user->can('generer-bulletins'), 403);
        $this->ensureTeacherAssignedToClassroom($user, $classroom);

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

    private function isStudentOrParentOf(User $user, User $student): bool
    {
        if ($user->id === $student->id && $user->hasRole('eleve')) {
            return true;
        }

        if ($user->hasRole('parent') && method_exists($user, 'parentProfile')) {
            $parentProfile = $user->parentProfile()->first();

            if ($parentProfile) {
                return $parentProfile->students()->where('users.id', $student->id)->exists();
            }
        }

        return false;
    }

    private function ensureTeacherAssignedToStudent(User $user, User $student): void
    {
        if (! $user->hasRole('professeur') || $user->hasAnyRole(['super-admin', 'admin'])) {
            return;
        }

        $teacher = $user->teacher;
        $classroomId = optional($student->latestRegistration)->classroom_id;

        abort_unless(
            $teacher && $classroomId && $this->isAssignedToClassroom($teacher, $classroomId),
            403,
            'Vous n\'êtes pas affecté à la classe de cet élève.'
        );
    }

    private function ensureTeacherAssignedToClassroom(User $user, Classroom $classroom): void
    {
        if (! $user->hasRole('professeur') || $user->hasAnyRole(['super-admin', 'admin'])) {
            return;
        }

        $teacher = $user->teacher;

        abort_unless(
            $teacher && $this->isAssignedToClassroom($teacher, $classroom->id),
            403,
            'Vous n\'êtes pas affecté à cette classe.'
        );
    }

    /**
     * PedagogicalAssignment (écran "Affectations pédagogiques") est la seule source de
     * vérité des affectations enseignant/classe alimentée par l'administration —
     * l'ancien pivot teacher_classroom (Teacher::classrooms()) n'est plus jamais
     * renseigné, voir GradeController::index() pour le même correctif.
     */
    private function isAssignedToClassroom($teacher, int $classroomId): bool
    {
        return PedagogicalAssignment::where('teacher_id', $teacher->id)
            ->where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->exists();
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
