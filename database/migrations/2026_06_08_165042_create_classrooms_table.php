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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Classe de CP", "Terminal S1 A"
            $table->string('cycle'); // primaire, college, lycee
            
            // Clé étrangère vers l'année scolaire
            $table->foreignId('school_year_id')->constrained()->onDelete('cascade');
            
            // Enseignant titulaire : Optionnel
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    } 

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};