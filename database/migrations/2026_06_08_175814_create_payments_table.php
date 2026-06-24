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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // On lie le paiement à l'inscription de l'élève pour cette année-là
            $table->foreignId('registration_id')->constrained()->onDelete('cascade');
            
            // Détails financiers
            $table->decimal('amount', 10, 2); // Le montant versé en FCFA
            $table->date('payment_date')->default(now()); // La date à laquelle l'argent a été encaissé
            
            // Type et suivi du paiement
            $table->string('payment_method')->default('especes'); // especes, wave, orange_money, cheque, virement
            $table->string('payment_type')->default('mensualite'); // mensualite, inscription, tenue, autre
            
            // Si c'est une mensualité, on précise quel mois est payé
            $table->string('month_paid')->nullable(); // Ex: "Octobre", "Novembre", "Décembre"
            
            // Numéro de reçu unique pour la comptabilité (Ex: REC-2026-0001)
            $table->string('receipt_number')->unique()->nullable();
            
            $table->string('comment')->nullable(); // Ex: "Payé en avance par le tuteur"
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
