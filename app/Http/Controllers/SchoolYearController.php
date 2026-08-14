<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolYearRequest;
use App\Http\Requests\UpdateSchoolYearRequest;
use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Services\AdminSecurityCodeService;
use App\Services\AuditLogService;
use App\Services\SchoolYearClosureChecklistService;
use App\Services\SchoolYearConfigDuplicationService;
use App\Support\SchoolYearStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolYearController extends Controller
{
    public function __construct(
        private AuditLogService $auditLog,
        private SchoolYearClosureChecklistService $closureChecklistService,
        private SchoolYearConfigDuplicationService $duplicationService,
    ) {}

    public function index(): View
    {
        $this->authorize('voir-annees-scolaires');

        $schoolYears = SchoolYear::orderBy('year_string', 'desc')->get();

        return view('school_years.index', compact('schoolYears'));
    }

    public function store(StoreSchoolYearRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $duplicateFromId = $validated['duplicate_from_id'] ?? null;
        unset($validated['duplicate_from_id']);

        $schoolYear = \DB::transaction(function () use ($validated) {
            $validated['status'] = ($validated['is_active'] ?? false) ? SchoolYearStatus::ACTIVE : SchoolYearStatus::PREPARATION;

            return SchoolYear::create($validated);
        });

        $message = 'Année scolaire '.$schoolYear->year_string.' créée avec succès.';

        if ($duplicateFromId) {
            $source = SchoolYear::findOrFail($duplicateFromId);
            $summary = $this->duplicationService->duplicate($source, $schoolYear);

            $this->auditLog->log(
                'school_year_config_duplicated',
                SchoolYear::class,
                $schoolYear->id,
                null,
                $summary,
                "Configuration dupliquée depuis {$source->year_string} vers {$schoolYear->year_string}."
            );

            $message .= sprintf(
                ' Configuration dupliquée depuis %s : %d classe(s), %d période(s), %d configuration(s) de matière, %d affectation(s) pédagogique(s), %d grille(s) tarifaire(s)%s.',
                $source->year_string,
                $summary['classrooms'],
                $summary['periods'],
                $summary['subject_configurations'],
                $summary['pedagogical_assignments'],
                $summary['classroom_fees'],
                $summary['grade_settings'] ? ', règles de notes reprises' : ''
            );
        }

        return redirect()
            ->route('school-years.index')
            ->with('success', $message);
    }

    public function edit(SchoolYear $schoolYear): View
    {
        $this->authorize('modifier-annee-scolaire', $schoolYear);

        return view('school_years.edit', compact('schoolYear'));
    }

    public function update(UpdateSchoolYearRequest $request, SchoolYear $schoolYear): RedirectResponse
    {
        $this->authorize('modifier-annee-scolaire', $schoolYear);

        $oldValues = $schoolYear->only(['year_string', 'start_date', 'end_date']);

        $schoolYear->update($request->validated());

        $this->auditLog->log(
            'school_year_updated',
            SchoolYear::class,
            $schoolYear->id,
            $oldValues,
            $schoolYear->only(['year_string', 'start_date', 'end_date']),
            "Année scolaire {$schoolYear->year_string} modifiée."
        );

        return redirect()
            ->route('school-years.index')
            ->with('success', 'Année scolaire '.$schoolYear->year_string.' mise à jour avec succès.');
    }

    public function activate(SchoolYear $schoolYear): RedirectResponse
    {
        $this->authorize('activer-annee-scolaire', $schoolYear);

        if ($schoolYear->is_active) {
            return redirect()
                ->back()
                ->with('info', 'L\'année scolaire '.$schoolYear->year_string.' est déjà active.');
        }

        // Une année clôturée ne se réactive pas comme une année en préparation : elle passe
        // par la réouverture exceptionnelle (reconfirmation de mot de passe, réservée au
        // Super Administrateur, voir reopen()).
        if ($schoolYear->status === SchoolYearStatus::CLOSED) {
            return redirect()
                ->back()
                ->with('error', 'Cette année est clôturée. Utilisez la réouverture exceptionnelle (réservée au Super Administrateur) plutôt que l\'activation classique.');
        }

        $fromStatus = $schoolYear->status;
        $schoolYear->activate();

        $this->auditLog->log(
            'school_year_activated',
            SchoolYear::class,
            $schoolYear->id,
            ['status' => $fromStatus],
            ['status' => $schoolYear->status],
            "Année scolaire {$schoolYear->year_string} activée."
        );

        return redirect()
            ->back()
            ->with('success', 'L\'année scolaire '.$schoolYear->year_string.' est maintenant active.');
    }

    /**
     * Rapport en lecture seule des anomalies à examiner avant de clôturer cette année
     * (comptabilité/pédagogie/administration). N'empêche encore rien : le blocage réel de
     * la clôture sur anomalie sera ajouté dans une sous-étape ultérieure.
     */
    public function closureChecklist(SchoolYear $schoolYear): View
    {
        $this->authorize('cloturer-annee-scolaire', $schoolYear);

        $checklist = $this->closureChecklistService->check($schoolYear);

        return view('school_years.closure_checklist', compact('schoolYear', 'checklist'));
    }

    /**
     * Démarre la clôture d'une année active (transition active -> closing). Purement
     * mécanique à ce stade : les vérifications comptables/pédagogiques/administratives
     * bloquantes seront ajoutées dans une sous-étape ultérieure.
     */
    public function startClosing(SchoolYear $schoolYear): RedirectResponse
    {
        $this->authorize('cloturer-annee-scolaire', $schoolYear);

        $schoolYear->transitionTo(SchoolYearStatus::CLOSING);

        $this->auditLog->log(
            'school_year_closing_started',
            SchoolYear::class,
            $schoolYear->id,
            null,
            ['status' => $schoolYear->status],
            "Démarrage de la clôture de l'année scolaire {$schoolYear->year_string}."
        );

        return redirect()
            ->back()
            ->with('success', 'Clôture de l\'année scolaire '.$schoolYear->year_string.' démarrée.');
    }

    /**
     * Annule une clôture en cours (transition closing -> active), sans effet sur les
     * données déjà verrouillées puisque le verrouillage complet n'existe pas encore
     * (sous-étape C).
     */
    public function cancelClosing(SchoolYear $schoolYear): RedirectResponse
    {
        $this->authorize('cloturer-annee-scolaire', $schoolYear);

        $schoolYear->transitionTo(SchoolYearStatus::ACTIVE);

        $this->auditLog->log(
            'school_year_closing_cancelled',
            SchoolYear::class,
            $schoolYear->id,
            null,
            ['status' => $schoolYear->status],
            "Clôture de l'année scolaire {$schoolYear->year_string} annulée."
        );

        return redirect()
            ->back()
            ->with('success', 'Clôture de l\'année scolaire '.$schoolYear->year_string.' annulée.');
    }

    /**
     * Formulaire de réouverture exceptionnelle d'une année clôturée. Étape distincte de
     * l'activation classique : réservée au Super Administrateur.
     */
    public function showReopenForm(SchoolYear $schoolYear): View
    {
        $this->authorize('deverrouiller-annee-scolaire', $schoolYear);

        abort_unless($schoolYear->status === SchoolYearStatus::CLOSED, 422, 'Seule une année clôturée peut être rouverte exceptionnellement.');

        return view('school_years.reopen', compact('schoolYear'));
    }

    public function reopen(Request $request, SchoolYear $schoolYear, AdminSecurityCodeService $securityCode): RedirectResponse
    {
        $this->authorize('deverrouiller-annee-scolaire', $schoolYear);

        abort_unless($schoolYear->status === SchoolYearStatus::CLOSED, 422, 'Seule une année clôturée peut être rouverte exceptionnellement.');

        // Reconfirmation du mot de passe (protection déjà en place) + code de sécurité
        // administrateur une fois que l'auteur en a défini un (mission Sécurité livrée
        // depuis l'écriture de cette action — voir AdminSecurityCodeService::ensureVerified()).
        // Les deux se cumulent plutôt que de remplacer l'un par l'autre : aucune régression
        // de protection pour les comptes n'ayant pas encore configuré de code.
        $request->validateWithBag('schoolYearReopen', [
            'password' => ['required', 'current_password'],
        ]);
        $securityCode->ensureVerified($request->user(), $request->input('security_code'));

        $fromStatus = $schoolYear->status;
        $schoolYear->transitionTo(SchoolYearStatus::ACTIVE);

        $this->auditLog->log(
            'school_year_reopened',
            SchoolYear::class,
            $schoolYear->id,
            ['status' => $fromStatus],
            ['status' => $schoolYear->status],
            "Réouverture exceptionnelle de l'année scolaire {$schoolYear->year_string} par un Super Administrateur."
        );

        return redirect()
            ->route('school-years.index')
            ->with('success', 'Année scolaire '.$schoolYear->year_string.' rouverte exceptionnellement.');
    }

    public function destroy(Request $request, SchoolYear $schoolYear, AdminSecurityCodeService $securityCode): RedirectResponse
    {
        $this->authorize('supprimer-annee-scolaire', $schoolYear);

        // Action critique listée au cahier des charges sécurité : exige le code
        // de sécurité administrateur (distinct du mot de passe) une fois que
        // l'auteur en a défini un — voir AdminSecurityCodeService::ensureVerified().
        $securityCode->ensureVerified($request->user(), $request->input('security_code'));

        if ($schoolYear->is_active) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer l\'année scolaire active. Veuillez d\'abord activer une autre année.');
        }

        $classroomsCount = Classroom::where('school_year_id', $schoolYear->id)->count();
        $registrationsCount = Registration::where('school_year_id', $schoolYear->id)->count();

        if ($classroomsCount > 0 || $registrationsCount > 0) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer cette année scolaire : '.
                    ($classroomsCount > 0 ? $classroomsCount.' classe(s) ' : '').
                    ($classroomsCount > 0 && $registrationsCount > 0 ? 'et ' : '').
                    ($registrationsCount > 0 ? $registrationsCount.' inscription(s) ' : '').
                    'y sont rattachées. Veuillez les transférer ou les supprimer d\'abord.');
        }

        $yearString = $schoolYear->year_string;

        \DB::transaction(function () use ($schoolYear) {
            $schoolYear->delete();
        });

        return redirect()
            ->back()
            ->with('success', 'L\'année scolaire '.$yearString.' a été supprimée avec succès.');
    }
}
