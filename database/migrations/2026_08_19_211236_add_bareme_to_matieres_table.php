<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            // Barème de base par défaut (/20, comme le système de notation standard) : le
            // primaire peut le surcharger par cycle via subject_configurations.bareme (voir
            // GradeCalculationService::resolveBareme(), qui retombe ici en dernier recours).
            $table->decimal('bareme', 5, 2)->default(20)->after('coefficient');
        });
    }

    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->dropColumn('bareme');
        });
    }
};
