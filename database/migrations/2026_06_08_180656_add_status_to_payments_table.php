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
            // 'complet' si le mois est réglé, 'partiel' si le parent doit encore de l'argent
            $table->string('status')->default('complet')->after('amount');
            
            // Pour stocker le reste à payer sur ce mois si le paiement est partiel
            $table->decimal('remaining_balance', 10, 2)->default(0.00)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['status', 'remaining_balance']);
        });
    }
};
