<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La contrainte unique existante (user_id, matiere_id, type_evaluation, periode)
     * ne permettait qu'UNE seule note de type "devoir" par élève/matière/période —
     * impossible donc de respecter la règle "2 devoirs maximum" (config
     * edu.max_evaluations_per_period) sans un champ supplémentaire distinguant
     * devoir n°1 de devoir n°2. Ajouté ici plutôt que renommé en type_evaluation
     * "devoir_1"/"devoir_2" pour garder EvaluationTypeScope (devoir/composition)
     * inchangé partout ailleurs (saisie, bulletins, calcul de moyenne).
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedTinyInteger('evaluation_number')->default(1)->after('type_evaluation');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropUnique('notes_student_subject_type_period_unique');
            $table->unique(
                ['user_id', 'matiere_id', 'type_evaluation', 'evaluation_number', 'periode'],
                'notes_student_subject_type_number_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropUnique('notes_student_subject_type_number_period_unique');
            $table->unique(
                ['user_id', 'matiere_id', 'type_evaluation', 'periode'],
                'notes_student_subject_type_period_unique'
            );
            $table->dropColumn('evaluation_number');
        });
    }
};
