<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rend au niveau base de données la règle métier déjà vérifiée (mais en
     * check-then-act non atomique) par StudentEnrollmentService::reenroll() :
     * un élève ne peut avoir qu'une seule inscription par année scolaire. Sans
     * cette contrainte, un double-clic sur "Réinscrire" peut créer deux
     * inscriptions actives pour le même élève la même année.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->unique(['user_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'school_year_id']);
        });
    }
};
