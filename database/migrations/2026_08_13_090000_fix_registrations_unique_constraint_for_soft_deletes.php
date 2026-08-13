<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BDD-01 : la contrainte unique(user_id, school_year_id) ajoutée en
     * 2026_08_10_090000 ne tient pas compte de `deleted_at`. Une inscription
     * soft-supprimée (annulation, correction) reste dans l'index et bloque
     * définitivement toute réinscription de l'élève pour la même année via
     * les voies normales (StudentEnrollmentService::reenroll()).
     *
     * On remplace la contrainte unique "plate" par une contrainte unique
     * qui ne porte que sur les inscriptions actives (non supprimées),
     * de façon portable entre SQLite (dev) et MySQL (production cible).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS registrations_user_id_school_year_id_unique');
            DB::statement(
                'CREATE UNIQUE INDEX registrations_active_user_school_year_unique '.
                'ON registrations (user_id, school_year_id) WHERE deleted_at IS NULL'
            );

            return;
        }

        // MySQL/MariaDB ne supportent pas les index uniques filtrés (WHERE) :
        // on matérialise la condition via une colonne générée qui ne vaut
        // school_year_id que pour les lignes actives (NULL sinon). MySQL
        // considère plusieurs NULL comme distincts dans un index unique, ce
        // qui laisse les lignes soft-supprimées ne plus entrer en conflit.
        DB::statement('ALTER TABLE registrations DROP INDEX registrations_user_id_school_year_id_unique');
        DB::statement(
            'ALTER TABLE registrations ADD COLUMN active_school_year_id BIGINT UNSIGNED '.
            'GENERATED ALWAYS AS (IF(deleted_at IS NULL, school_year_id, NULL)) VIRTUAL'
        );
        DB::statement(
            'ALTER TABLE registrations ADD UNIQUE INDEX registrations_active_user_school_year_unique '.
            '(user_id, active_school_year_id)'
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS registrations_active_user_school_year_unique');
            DB::statement(
                'CREATE UNIQUE INDEX registrations_user_id_school_year_id_unique ON registrations (user_id, school_year_id)'
            );

            return;
        }

        DB::statement('ALTER TABLE registrations DROP INDEX registrations_active_user_school_year_unique');
        DB::statement('ALTER TABLE registrations DROP COLUMN active_school_year_id');
        DB::statement('ALTER TABLE registrations ADD UNIQUE INDEX registrations_user_id_school_year_id_unique (user_id, school_year_id)');
    }
};
