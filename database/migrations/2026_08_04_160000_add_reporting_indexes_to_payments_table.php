<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index de fiabilisation/performance pour les requêtes comptables lourdes
 * (dashboard, rapports, alertes) qui filtrent systématiquement par
 * statut, annulation et date de paiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // payment_date, status et registration_id sont déjà indexés individuellement
            // (cf. 2026_07_13_103344_add_indexes_to_tables.php) ; on ajoute ici les index
            // composites réellement utilisés par les requêtes de reporting/annulation.
            $table->index(['status', 'cancelled_at'], 'payments_status_cancelled_at_index');
            $table->index(['registration_id', 'status', 'cancelled_at'], 'payments_registration_status_cancelled_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_cancelled_at_index');
            $table->dropIndex('payments_registration_status_cancelled_index');
        });
    }
};
