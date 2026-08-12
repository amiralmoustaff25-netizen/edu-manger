<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Note;
use App\Models\Payment;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use App\Support\StudentStatus;

/**
 * Calcule, pour une année scolaire donnée, la liste des anomalies à examiner avant sa
 * clôture (comptabilité / pédagogie / administration). Service en LECTURE SEULE : il ne
 * bloque rien lui-même — le verrouillage réel de la clôture (sous-étape C) s'appuiera sur
 * hasBlockingAnomalies().
 *
 * Certains points du cahier des charges n'ont aucune donnée exploitable dans le schéma
 * actuel (remises non validées, conseils de classe, bulletins "générés") : ils sont rendus
 * avec le statut 'not_applicable' plutôt que d'être tus ou faussement évalués — voir le
 * champ 'note' de chaque anomalie concernée.
 */
class SchoolYearClosureChecklistService
{
    public function __construct(private FeeService $feeService) {}

    public function check(SchoolYear $schoolYear): array
    {
        return [
            'comptabilite' => [
                $this->checkOutstandingBalances($schoolYear),
                $this->checkPendingPartialPayments($schoolYear),
                $this->checkUnvalidatedDiscounts(),
                $this->checkUnconfiguredFees($schoolYear),
            ],
            'pedagogie' => [
                $this->checkMissingGrades($schoolYear),
                $this->checkUnvalidatedGrades($schoolYear),
                $this->checkGeneratedBulletins(),
                $this->checkClassCouncils(),
            ],
            'administration' => [
                $this->checkStudentsWithoutClassroom($schoolYear),
                $this->checkTeachersWithoutAssignment($schoolYear),
                $this->checkMissingDocuments($schoolYear),
                $this->checkIncompleteRegistrations($schoolYear),
            ],
        ];
    }

    public function hasBlockingAnomalies(SchoolYear $schoolYear): bool
    {
        return collect($this->check($schoolYear))
            ->flatten(1)
            ->contains(fn (array $item) => $item['status'] === 'anomaly');
    }

    public function anomalyCount(SchoolYear $schoolYear): int
    {
        return collect($this->check($schoolYear))
            ->flatten(1)
            ->where('status', 'anomaly')
            ->sum('count');
    }

    /**
     * Fusionne "paiements non terminés" et "impayés" du cahier des charges : les deux
     * pointent vers le même signal fiable, le solde restant dû calculé par FeeService
     * (seule source de vérité financière du projet — ne jamais sommer les paiements bruts).
     */
    private function checkOutstandingBalances(SchoolYear $schoolYear): array
    {
        $registrations = Registration::where('school_year_id', $schoolYear->id)
            ->where('status', StudentStatus::ACTIVE)
            ->with('user')
            ->get();

        $withBalance = $registrations->filter(function (Registration $registration) {
            return $this->feeService->getFinancialSituation($registration)['remaining'] > 0;
        });

        return $this->result(
            'paiements_non_termines',
            'Élèves avec un solde restant dû (paiements non terminés / impayés)',
            $withBalance->count() > 0 ? 'anomaly' : 'ok',
            $withBalance->count(),
            $withBalance->map(fn (Registration $r) => $r->user?->name ?? "Inscription #{$r->id}")->values()->all()
        );
    }

    private function checkPendingPartialPayments(SchoolYear $schoolYear): array
    {
        $registrationIds = Registration::where('school_year_id', $schoolYear->id)->pluck('id');

        $count = Payment::whereIn('registration_id', $registrationIds)
            ->pendingValidation()
            ->count();

        return $this->result(
            'paiements_partiels_non_valides',
            'Paiements partiels en attente de validation',
            $count > 0 ? 'anomaly' : 'ok',
            $count
        );
    }

    private function checkUnvalidatedDiscounts(): array
    {
        return $this->result(
            'remises_non_validees',
            'Remises non validées',
            'not_applicable',
            0,
            note: "Le modèle Discount n'a aucun concept de validation : une remise est appliquée immédiatement à sa création, il n'existe rien à vérifier ici avec les données actuelles."
        );
    }

    /**
     * Interprétation concrète du point "anomalies comptables" : inscriptions dont les frais
     * n'ont jamais été correctement configurés (montant à 0), signe probable d'une grille
     * tarifaire manquante pour la classe/année plutôt que d'un choix délibéré.
     */
    private function checkUnconfiguredFees(SchoolYear $schoolYear): array
    {
        $registrations = Registration::where('school_year_id', $schoolYear->id)
            ->where(function ($query) {
                $query->where('monthly_fee', 0)->orWhere('registration_fee_paid', 0);
            })
            ->with('user')
            ->get();

        return $this->result(
            'anomalies_comptables',
            'Inscriptions avec des frais à 0 (grille tarifaire probablement non configurée)',
            $registrations->count() > 0 ? 'anomaly' : 'ok',
            $registrations->count(),
            $registrations->map(fn (Registration $r) => $r->user?->name ?? "Inscription #{$r->id}")->values()->all()
        );
    }

    /**
     * Combinaisons classe+matière activement affectées cette année scolaire sans une
     * seule note saisie pour les élèves de cette classe.
     */
    private function checkMissingGrades(SchoolYear $schoolYear): array
    {
        $assignments = PedagogicalAssignment::where('school_year_id', $schoolYear->id)
            ->where('is_active', true)
            ->with(['classroom', 'matiere'])
            ->get();

        $missing = $assignments->filter(function (PedagogicalAssignment $assignment) {
            return ! Note::where('classroom_id', $assignment->classroom_id)
                ->where('matiere_id', $assignment->matiere_id)
                ->exists();
        });

        return $this->result(
            'notes_non_saisies',
            'Affectations classe/matière sans aucune note saisie',
            $missing->count() > 0 ? 'anomaly' : 'ok',
            $missing->count(),
            $missing->map(fn (PedagogicalAssignment $a) => "{$a->classroom?->name} — {$a->matiere?->nom}")->values()->all()
        );
    }

    private function checkUnvalidatedGrades(SchoolYear $schoolYear): array
    {
        $classroomIds = Classroom::where('school_year_id', $schoolYear->id)->pluck('id');

        $count = Note::whereIn('classroom_id', $classroomIds)->notValidated()->count();

        return $this->result(
            'notes_non_validees',
            'Notes saisies mais non validées',
            $count > 0 ? 'anomaly' : 'ok',
            $count
        );
    }

    private function checkGeneratedBulletins(): array
    {
        return $this->result(
            'bulletins_non_generes',
            'Bulletins non générés',
            'not_applicable',
            0,
            note: 'Les bulletins sont générés à la volée (PDF) sans jamais être persistés en base : il n\'existe aucun état "généré/non généré" à vérifier. Voir plutôt "notes non saisies/validées" ci-dessus, dont dépend la qualité d\'un bulletin généré à la demande.'
        );
    }

    private function checkClassCouncils(): array
    {
        return $this->result(
            'conseils_de_classe_non_termines',
            'Conseils de classe non terminés',
            'not_applicable',
            0,
            note: "Le concept de « conseil de classe » n'existe pas dans le modèle de données actuel (aucun modèle, migration ou vue ne le référence) — à construire si cette fonctionnalité doit réellement être suivie."
        );
    }

    /**
     * Structurellement rare : classroom_id est obligatoire (NOT NULL + validation) à la
     * création d'une inscription. Ce contrôle détecte plutôt les inscriptions dont la classe
     * a depuis été archivée (soft delete) sans que l'élève n'ait été transféré ailleurs.
     */
    private function checkStudentsWithoutClassroom(SchoolYear $schoolYear): array
    {
        $registrations = Registration::where('school_year_id', $schoolYear->id)
            ->where('status', StudentStatus::ACTIVE)
            ->whereDoesntHave('classroom')
            ->with('user')
            ->get();

        return $this->result(
            'eleves_sans_classe',
            'Élèves actifs dont la classe a été archivée entre-temps',
            $registrations->count() > 0 ? 'anomaly' : 'ok',
            $registrations->count(),
            $registrations->map(fn (Registration $r) => $r->user?->name ?? "Inscription #{$r->id}")->values()->all()
        );
    }

    private function checkTeachersWithoutAssignment(SchoolYear $schoolYear): array
    {
        $assignedTeacherIds = PedagogicalAssignment::where('school_year_id', $schoolYear->id)
            ->where('is_active', true)
            ->pluck('teacher_id');

        $teachers = Teacher::whereNotIn('id', $assignedTeacherIds)->with('user')->get();

        return $this->result(
            'enseignants_sans_affectation',
            'Enseignants sans aucune affectation cette année',
            $teachers->count() > 0 ? 'anomaly' : 'ok',
            $teachers->count(),
            $teachers->map(fn (Teacher $t) => $t->user?->name ?? "Professeur #{$t->id}")->values()->all()
        );
    }

    /**
     * Aucun type de document n'est marqué "obligatoire" dans le schéma actuel : ce contrôle
     * reste informatif (élèves n'ayant déposé aucun document) plutôt qu'un vrai contrôle de
     * complétude, faute de liste de documents requis définie quelque part.
     */
    private function checkMissingDocuments(SchoolYear $schoolYear): array
    {
        $studentIds = Registration::where('school_year_id', $schoolYear->id)
            ->where('status', StudentStatus::ACTIVE)
            ->pluck('user_id');

        $withoutDocuments = User::whereIn('id', $studentIds)
            ->whereDoesntHave('documents')
            ->get();

        return $this->result(
            'documents_manquants',
            'Élèves n\'ayant déposé aucun document administratif',
            $withoutDocuments->count() > 0 ? 'anomaly' : 'ok',
            $withoutDocuments->count(),
            $withoutDocuments->pluck('name')->values()->all(),
            note: "Aucun type de document n'est marqué obligatoire dans le schéma actuel — ce contrôle signale seulement l'absence totale de document, pas une liste précise de pièces manquantes."
        );
    }

    private function checkIncompleteRegistrations(SchoolYear $schoolYear): array
    {
        $count = Registration::where('school_year_id', $schoolYear->id)
            ->whereNull('matricule')
            ->count();

        return $this->result(
            'inscriptions_incompletes',
            'Inscriptions sans matricule attribué',
            $count > 0 ? 'anomaly' : 'ok',
            $count
        );
    }

    private function result(string $key, string $label, string $status, int $count, array $items = [], ?string $note = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'count' => $count,
            'items' => array_slice($items, 0, 20),
            'items_truncated' => count($items) > 20,
            'note' => $note,
        ];
    }
}
