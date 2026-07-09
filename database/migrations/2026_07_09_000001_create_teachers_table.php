<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('matricule')->unique();
            $table->date('date_naissance');
            $table->string('lieu_naissance');
            $table->enum('sexe', ['masculin', 'feminin']);
            $table->string('nationalite');
            $table->text('diplomes');
            $table->text('etablissements_formation');
            $table->enum('statut', ['fonctionnaire', 'contractuel', 'vacataire']);
            $table->date('date_recrutement');
            $table->json('specialites')->nullable();
            $table->text('filiation');
            $table->string('contact_urgence_nom');
            $table->string('contact_urgence_tel');
            $table->string('rib')->nullable();
            $table->unsignedInteger('nombre_heures_semaine')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
