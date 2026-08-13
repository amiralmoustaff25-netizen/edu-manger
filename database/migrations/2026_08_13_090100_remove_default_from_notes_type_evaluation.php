<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BDD-16 : la valeur par défaut 'devoir' au niveau colonne est en
     * contradiction avec EvaluationTypeScope::allowedFor(), qui interdit
     * ce type pour le cycle primaire (seul 'composition' y est autorisé).
     * Le type d'évaluation doit toujours être fourni explicitement par le
     * code applicatif plutôt que de reposer sur un défaut de schéma.
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->string('type_evaluation')->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->string('type_evaluation')->default('devoir')->change();
        });
    }
};
