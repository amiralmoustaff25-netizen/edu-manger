<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MET-02/MET-09 : matérialise la correspondance "cette classe promeut vers
     * telle classe l'année suivante" (ex. CM1 A 2025-2026 -> CM2 A 2026-2027),
     * condition nécessaire à :
     *   - la promotion de classe en masse (PromotionController) ;
     *   - la préselection de la classe supérieure sur le formulaire de
     *     réinscription individuelle (au lieu de rester par défaut sur
     *     l'ancienne classe de l'élève).
     */
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('promotes_to_classroom_id')
                ->nullable()
                ->after('school_year_id')
                ->constrained('classrooms')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['promotes_to_classroom_id']);
            $table->dropColumn('promotes_to_classroom_id');
        });
    }
};
