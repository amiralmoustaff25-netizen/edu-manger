<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\ClassroomFee;
use App\Models\GradeSetting;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\SubjectConfiguration;
use Illuminate\Support\Facades\DB;

/**
 * Duplique vers une nouvelle année scolaire UNIQUEMENT les données de configuration de
 * l'année source — jamais l'historique (notes, paiements, présences, inscriptions...).
 *
 * Décision de conception : les grilles tarifaires (ClassroomFee) et affectations
 * pédagogiques (PedagogicalAssignment) sont rattachées à une CLASSE précise, pas
 * directement à l'année — et les classes sont elles-mêmes recréées chaque année
 * (Classroom.school_year_id). Dupliquer la configuration implique donc de recréer aussi
 * des classes "coquilles vides" (mêmes nom/cycle/effectif/enseignant titulaire, aucun
 * élève) sur lesquelles rattacher le reste. L'affectation des élèves à ces classes reste
 * entièrement du ressort de la promotion/réinscription (sous-étape E), jamais de ce service.
 *
 * Explicitement NON dupliqué, faute de structure de données exploitable ou par nature
 * globale (voir mémoire projet "Spec : Années scolaires") :
 * - rôles/permissions Spatie et types d'évaluation/frais (evaluation_types, fee_types) :
 *   globaux à l'application, aucune notion d'année, rien à dupliquer ;
 * - "modèles de bulletins" : aucune entité de ce type n'existe (bulletins générés à la volée) ;
 * - program_annuals : mélange configuration ET suivi transactionnel (status, submitted_at,
 *   validations) — une nouvelle année doit repartir d'un programme vierge, pas d'une copie
 *   du statut de soumission de l'année précédente.
 */
class SchoolYearConfigDuplicationService
{
    public function duplicate(SchoolYear $source, SchoolYear $target): array
    {
        return DB::transaction(function () use ($source, $target) {
            $classroomMap = $this->duplicateClassrooms($source, $target);

            return [
                'classrooms' => count($classroomMap),
                'periods' => $this->duplicatePeriods($source, $target),
                'grade_settings' => $this->duplicateGradeSettings($source, $target),
                'subject_configurations' => $this->duplicateSubjectConfigurations($source, $target, $classroomMap),
                'pedagogical_assignments' => $this->duplicatePedagogicalAssignments($source, $target, $classroomMap),
                'classroom_fees' => $this->duplicateClassroomFees($source, $target, $classroomMap),
            ];
        });
    }

    /** @return array<int,int> ancien classroom_id => nouveau classroom_id */
    private function duplicateClassrooms(SchoolYear $source, SchoolYear $target): array
    {
        $map = [];

        Classroom::where('school_year_id', $source->id)->get()->each(function (Classroom $classroom) use ($target, &$map) {
            $new = Classroom::create([
                'name' => $classroom->name,
                'cycle' => $classroom->cycle,
                'school_year_id' => $target->id,
                'teacher_id' => $classroom->teacher_id,
                'max_students' => $classroom->max_students,
            ]);

            $map[$classroom->id] = $new->id;
        });

        return $map;
    }

    private function duplicatePeriods(SchoolYear $source, SchoolYear $target): int
    {
        $count = 0;

        AcademicPeriod::where('school_year_id', $source->id)->get()->each(function (AcademicPeriod $period) use ($target, &$count) {
            AcademicPeriod::create([
                'school_year_id' => $target->id,
                'name' => $period->name,
                'code' => $period->code,
                'position' => $period->position,
                // Dates décalées d'un an par défaut (simple point de départ, à ajuster
                // manuellement) : l'année scolaire suivante n'a pas les mêmes dates que
                // l'ancienne, mais reprend en général le même calendrier approximatif.
                'starts_at' => $period->starts_at?->copy()->addYear(),
                'ends_at' => $period->ends_at?->copy()->addYear(),
                'grade_entry_starts_at' => $period->grade_entry_starts_at?->copy()->addYear(),
                'grade_entry_ends_at' => $period->grade_entry_ends_at?->copy()->addYear(),
                // Jamais l'état d'exécution de l'ancienne période : une période dupliquée
                // repart toujours fermée à la saisie, quel que soit l'état de l'originale.
                'status' => 'draft',
                'grade_entry_open' => false,
            ]);
            $count++;
        });

        return $count;
    }

    private function duplicateGradeSettings(SchoolYear $source, SchoolYear $target): bool
    {
        $settings = GradeSetting::where('school_year_id', $source->id)->first();

        if (! $settings) {
            return false;
        }

        GradeSetting::create([
            'school_year_id' => $target->id,
            'organization_mode' => $settings->organization_mode,
            'default_scale' => $settings->default_scale,
            'minimum_grade' => $settings->minimum_grade,
            'allow_decimals' => $settings->allow_decimals,
            'decimal_places' => $settings->decimal_places,
            'allow_appreciations' => $settings->allow_appreciations,
            'allow_edit_after_submission' => $settings->allow_edit_after_submission,
            'administrative_validation_required' => $settings->administrative_validation_required,
            'lock_after_validation' => $settings->lock_after_validation,
        ]);

        return true;
    }

    /** @param array<int,int> $classroomMap */
    private function duplicateSubjectConfigurations(SchoolYear $source, SchoolYear $target, array $classroomMap): int
    {
        $count = 0;

        SubjectConfiguration::where('school_year_id', $source->id)->get()->each(function (SubjectConfiguration $config) use ($target, $classroomMap, &$count) {
            SubjectConfiguration::create([
                'matiere_id' => $config->matiere_id,
                'school_year_id' => $target->id,
                'cycle' => $config->cycle,
                'level' => $config->level,
                'classroom_id' => $config->classroom_id ? ($classroomMap[$config->classroom_id] ?? null) : null,
                'coefficient' => $config->coefficient,
                'is_active' => $config->is_active,
            ]);
            $count++;
        });

        return $count;
    }

    /**
     * Seules les affectations encore actives sont dupliquées — pas de raison de reconduire
     * une affectation déjà désactivée dans l'ancienne année.
     *
     * @param  array<int,int>  $classroomMap
     */
    private function duplicatePedagogicalAssignments(SchoolYear $source, SchoolYear $target, array $classroomMap): int
    {
        $count = 0;

        PedagogicalAssignment::where('school_year_id', $source->id)->where('is_active', true)->get()
            ->each(function (PedagogicalAssignment $assignment) use ($target, $classroomMap, &$count) {
                if (! isset($classroomMap[$assignment->classroom_id])) {
                    return;
                }

                PedagogicalAssignment::create([
                    'teacher_id' => $assignment->teacher_id,
                    'classroom_id' => $classroomMap[$assignment->classroom_id],
                    'matiere_id' => $assignment->matiere_id,
                    'school_year_id' => $target->id,
                    'volume_horaire_hebdo' => $assignment->volume_horaire_hebdo,
                    'is_active' => true,
                ]);
                $count++;
            });

        return $count;
    }

    /**
     * Seules les grilles tarifaires "courantes" (is_current) sont dupliquées, en version 1
     * fraîche — l'historique de versioning de l'ancienne année n'a pas de sens à reporter.
     *
     * @param  array<int,int>  $classroomMap
     */
    private function duplicateClassroomFees(SchoolYear $source, SchoolYear $target, array $classroomMap): int
    {
        $count = 0;

        ClassroomFee::where('school_year_id', $source->id)->current()->get()
            ->each(function (ClassroomFee $fee) use ($target, $classroomMap, &$count) {
                if (! isset($classroomMap[$fee->classroom_id])) {
                    return;
                }

                ClassroomFee::create([
                    'classroom_id' => $classroomMap[$fee->classroom_id],
                    'fee_type_id' => $fee->fee_type_id,
                    'school_year_id' => $target->id,
                    'amount' => $fee->amount,
                    'version' => 1,
                    'is_current' => true,
                ]);
                $count++;
            });

        return $count;
    }
}
