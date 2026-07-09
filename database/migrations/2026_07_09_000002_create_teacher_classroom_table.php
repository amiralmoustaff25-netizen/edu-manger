<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_classroom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->string('annee_scolaire');
            $table->foreignId('matiere_id')->nullable()->constrained('matieres')->nullOnDelete();
            $table->unsignedInteger('volume_horaire_hebdo')->default(0);
            $table->timestamps();
            $table->unique(['teacher_id', 'classroom_id', 'annee_scolaire'], 'teacher_classroom_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_classroom');
    }
};
