<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_configurations', function (Blueprint $table) {
            // Série du lycée (ex. L, S, ES) : un même cycle "lycee" peut avoir des
            // coefficients différents par matière selon la série (ex. Maths coef. 4 en
            // Série S, coef. 2 en Série L) — voir PedagogicalConfigurationController::
            // storeSubjectConfiguration().
            $table->string('serie')->nullable()->after('cycle');
        });
    }

    public function down(): void
    {
        Schema::table('subject_configurations', function (Blueprint $table) {
            $table->dropColumn('serie');
        });
    }
};
