<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_annuals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('brouillon');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('validated_by_surveillant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by_directeur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['classroom_id', 'subject_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_annuals');
    }
};
