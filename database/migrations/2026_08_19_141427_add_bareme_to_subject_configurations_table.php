<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subject_configurations', function (Blueprint $table) {
            // Primaire (système "sunuBulletin") : chaque matière a son propre barème
            // (note maximale de l'évaluation), qui sert aussi de poids dans la moyenne
            // générale — remplace le couple coefficient+/20 uniforme utilisé au
            // collège/lycée, jamais adapté à ce système. Nullable : ne s'applique qu'aux
            // matières de primaire explicitement configurées, le collège/lycée continue
            // d'utiliser 'coefficient' exactement comme avant.
            $table->decimal('bareme', 5, 2)->nullable()->after('coefficient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_configurations', function (Blueprint $table) {
            $table->dropColumn('bareme');
        });
    }
};
