<?php

use App\Support\StudentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * registrations.status n'acceptait que 'pending'/'active' (ENUM d'origine), alors que
     * App\Support\StudentStatus::ALL en définit 8 (suspended, transferred, graduated,
     * withdrawn, expelled, cancelled en plus) — utilisées par StudentPromotionService et
     * StudentController::updateStatus() depuis leur création. Toute transition vers l'une
     * de ces 6 valeurs échouait silencieusement en local (SQLite n'impose aucun ENUM) mais
     * plante réellement sur MySQL (SQLSTATE 1265, "Data truncated for column 'status'") —
     * jamais détecté avant l'exécution du CI sur une vraie base MySQL.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->enum('status', StudentStatus::ALL)->default(StudentStatus::PENDING)->change();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active'])->default('pending')->change();
        });
    }
};
