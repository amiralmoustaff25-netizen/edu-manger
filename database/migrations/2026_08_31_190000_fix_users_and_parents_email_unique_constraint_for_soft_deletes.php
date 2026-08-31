<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Même correctif que 2026_08_13_090000 (registrations) : la contrainte
     * unique(email) sur `users` et `parents` ne tient pas compte de `deleted_at`.
     * Un compte archivé garde son email dans l'index et bloque définitivement sa
     * réutilisation (constaté concrètement : DatabaseSeeder plantait sur un email
     * de démo déjà pris par un compte non lié, et la validation applicative de
     * StoreParentRequest/UpdateParentRequest/UpdateStudentRequest refusait à tort
     * la réutilisation d'un email d'archive alors que ces Form Requests avaient
     * justement été corrigées pour l'autoriser).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS users_email_unique');
            DB::statement(
                'CREATE UNIQUE INDEX users_active_email_unique ON users (email) WHERE deleted_at IS NULL'
            );

            DB::statement('DROP INDEX IF EXISTS parents_email_unique');
            DB::statement(
                'CREATE UNIQUE INDEX parents_active_email_unique ON parents (email) WHERE deleted_at IS NULL'
            );

            return;
        }

        // MySQL/MariaDB ne supportent pas les index uniques filtrés (WHERE) : colonne
        // générée qui ne vaut email que pour les lignes actives (NULL sinon). MySQL
        // considère plusieurs NULL comme distincts dans un index unique.
        DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
        DB::statement(
            'ALTER TABLE users ADD COLUMN active_email VARCHAR(255) '.
            'GENERATED ALWAYS AS (IF(deleted_at IS NULL, email, NULL)) VIRTUAL'
        );
        DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_active_email_unique (active_email)');

        DB::statement('ALTER TABLE parents DROP INDEX parents_email_unique');
        DB::statement(
            'ALTER TABLE parents ADD COLUMN active_email VARCHAR(255) '.
            'GENERATED ALWAYS AS (IF(deleted_at IS NULL, email, NULL)) VIRTUAL'
        );
        DB::statement('ALTER TABLE parents ADD UNIQUE INDEX parents_active_email_unique (active_email)');
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS users_active_email_unique');
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

            DB::statement('DROP INDEX IF EXISTS parents_active_email_unique');
            DB::statement('CREATE UNIQUE INDEX parents_email_unique ON parents (email)');

            return;
        }

        DB::statement('ALTER TABLE users DROP INDEX users_active_email_unique');
        DB::statement('ALTER TABLE users DROP COLUMN active_email');
        DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_email_unique (email)');

        DB::statement('ALTER TABLE parents DROP INDEX parents_active_email_unique');
        DB::statement('ALTER TABLE parents DROP COLUMN active_email');
        DB::statement('ALTER TABLE parents ADD UNIQUE INDEX parents_email_unique (email)');
    }
};
