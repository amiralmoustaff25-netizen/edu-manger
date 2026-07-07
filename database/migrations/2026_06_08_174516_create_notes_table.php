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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();

            // Clés étrangères : Liens indispensables
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // L'élève
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade'); // La classe
            $table->foreignId('matiere_id')->constrained('matieres')->onDelete('cascade'); // La matière

            // Détails de la note
            $table->decimal('valeur', 4, 2); // Ex: 15.50 ou 08.00 (sur 20)
            $table->string('type_evaluation')->default('devoir'); // devoir, composition, interrogation
            $table->string('periode'); // Trimestre 1, Semestre 1, etc.

            // Optionnel : appréciation du prof (ex: "Très bon travail", "En baisse")
            $table->string('appreciation')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
