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
        Schema::create('school_configurations', function (Blueprint $table) {
            $table->id();
            
            // Informations de l'école
            $table->string('school_name')->nullable();
            $table->string('school_logo')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // Informations bancaires
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift')->nullable();
            
            // Paramètres comptables
            $table->enum('overpayment_mode', ['change', 'credit'])->default('change');
            $table->boolean('sequential_payment_rule')->default(true);
            $table->boolean('allow_future_payment')->default(false);
            
            // Statut de configuration
            $table->boolean('is_configured')->default(false);
            $table->timestamp('configured_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_configurations');
    }
};
