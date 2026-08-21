<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            // Jour et créneau : chaînes fixes définies dans App\Support\TimetableGrid (pas de
            // table de référence séparée — la grille est la même pour toutes les classes).
            $table->string('day');
            $table->string('slot');
            // Contenu libre (matière, "Récréation", "Pause déjeuner"...) plutôt qu'une clé
            // étrangère vers matieres : une case ne correspond pas toujours à une matière
            // formelle, et un import Excel doit pouvoir écrire n'importe quel texte tel quel.
            $table->string('content')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'school_year_id', 'day', 'slot'], 'timetable_entry_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
    }
};
