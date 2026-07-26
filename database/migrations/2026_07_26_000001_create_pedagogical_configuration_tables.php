<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedagogical_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->unsignedInteger('volume_horaire_hebdo')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['teacher_id', 'classroom_id', 'matiere_id', 'school_year_id'], 'pedagogical_assignment_unique');
        });

        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedTinyInteger('position');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->date('grade_entry_starts_at')->nullable();
            $table->date('grade_entry_ends_at')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('grade_entry_open')->default(false);
            $table->timestamps();
            $table->unique(['school_year_id', 'code']);
        });

        Schema::create('evaluation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->decimal('default_coefficient', 5, 2)->default(1);
            $table->unsignedSmallInteger('default_scale')->default(20);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('grade_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->unique()->constrained('school_years')->cascadeOnDelete();
            $table->string('organization_mode')->default('trimesters');
            $table->unsignedSmallInteger('default_scale')->default(20);
            $table->decimal('minimum_grade', 5, 2)->default(0);
            $table->boolean('allow_decimals')->default(true);
            $table->unsignedTinyInteger('decimal_places')->default(1);
            $table->boolean('allow_appreciations')->default(true);
            $table->boolean('allow_edit_after_submission')->default(false);
            $table->boolean('administrative_validation_required')->default(false);
            $table->boolean('lock_after_validation')->default(false);
            $table->timestamps();
        });

        Schema::create('subject_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->string('cycle')->nullable();
            $table->string('level')->nullable();
            $table->foreignId('classroom_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_configurations');
        Schema::dropIfExists('grade_settings');
        Schema::dropIfExists('evaluation_types');
        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('pedagogical_assignments');
    }
};
