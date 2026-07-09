<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['avertissement_verbal', 'avertissement_ecrit', 'retenue', 'exclusion_temporaire', 'autre'])->default('autre');
            $table->text('description');
            $table->date('date_incident');
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mesure')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date_incident']);
        });

        Schema::create('student_class_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('school_year_id')->nullable()->constrained('school_years')->nullOnDelete();
            $table->string('annee_scolaire')->nullable();
            $table->string('resultat')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_history');
        Schema::dropIfExists('sanctions');
    }
};
