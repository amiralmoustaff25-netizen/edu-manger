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
        Schema::table('payments', function (Blueprint $table) {
            // Ajouter validated_at seulement s'il n'existe pas
            if (! Schema::hasColumn('payments', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }

            // receipt_number existe déjà, on ne l'ajoute pas

            // Modifier le statut pour inclure 'rejected'
            $table->enum('status', ['complet', 'partiel', 'rejected'])->default('complet')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
            // receipt_number existe déjà, on ne le supprime pas
            $table->enum('status', ['complet', 'partiel'])->default('complet')->change();
        });
    }
};
