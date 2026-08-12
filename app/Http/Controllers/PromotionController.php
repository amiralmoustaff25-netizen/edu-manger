<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Services\AuditLogService;
use App\Services\StudentPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        private StudentPromotionService $promotionService,
        private AuditLogService $auditLog,
    ) {}

    /**
     * Sélection de l'année source, puis aperçu (modifiable) des décisions suggérées pour
     * chaque élève avant toute exécution. La cible est toujours l'année scolaire active
     * (même contrainte que StudentEnrollmentService::reenroll()).
     */
    public function index(Request $request): View
    {
        $this->authorize('promouvoir-eleves');

        $schoolYears = SchoolYear::orderBy('year_string', 'desc')->get();
        $activeYear = SchoolYear::getActive();
        $sourceYear = null;
        $preview = [];

        if ($request->filled('source_year_id')) {
            $sourceYear = SchoolYear::findOrFail($request->integer('source_year_id'));
            $preview = $this->promotionService->preview($sourceYear);
        }

        $targetClassrooms = $activeYear
            ? Classroom::where('school_year_id', $activeYear->id)->orderBy('ordre')->orderBy('name')->get()
            : collect();

        return view('promotion.index', compact('schoolYears', 'activeYear', 'sourceYear', 'preview', 'targetClassrooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('promouvoir-eleves');

        $validated = $request->validate([
            'source_year_id' => ['required', 'exists:school_years,id'],
            'decisions' => ['required', 'array'],
            'decisions.*.action' => ['required', 'in:promote,repeat,transfer,graduate,expel,skip'],
            'decisions.*.classroom_id' => ['nullable', 'exists:classrooms,id'],
            'decisions.*.reason' => ['nullable', 'string', 'max:255'],
        ]);

        $results = $this->promotionService->apply($validated['decisions']);

        $sourceYear = SchoolYear::find($validated['source_year_id']);

        $this->auditLog->log(
            'students_promoted',
            SchoolYear::class,
            $sourceYear?->id,
            null,
            $results,
            sprintf(
                'Promotion depuis %s : %d promu(s), %d redoublant(s), %d diplômé(s), %d transféré(s), %d radié(s), %d ignoré(s), %d erreur(s).',
                $sourceYear?->year_string,
                $results['promoted'],
                $results['repeated'],
                $results['graduated'],
                $results['transferred'],
                $results['expelled'],
                $results['skipped'],
                count($results['errors'])
            )
        );

        $message = sprintf(
            '%d promu(s), %d redoublant(s), %d diplômé(s), %d transféré(s), %d radié(s).',
            $results['promoted'], $results['repeated'], $results['graduated'], $results['transferred'], $results['expelled']
        );

        if ($results['errors'] !== []) {
            return redirect()
                ->route('promotion.index', ['source_year_id' => $validated['source_year_id']])
                ->with('warning', $message.' '.count($results['errors']).' erreur(s) : '.implode(' | ', $results['errors']));
        }

        return redirect()
            ->route('promotion.index')
            ->with('success', $message);
    }
}
