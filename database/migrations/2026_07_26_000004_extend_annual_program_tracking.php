<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_annuals', function (Blueprint $table) {
            $table->foreignId('pedagogical_assignment_id')->nullable()->after('school_year_id')->constrained()->nullOnDelete();
            $table->foreignId('academic_period_id')->nullable()->after('pedagogical_assignment_id')->constrained()->nullOnDelete();
            $table->index('pedagogical_assignment_id');
        });

        Schema::table('program_chapters', function (Blueprint $table) {
            $table->foreignId('academic_period_id')->nullable()->after('description')->constrained()->nullOnDelete();
            $table->date('planned_at')->nullable()->after('academic_period_id');
            $table->string('status')->default('a_faire')->after('planned_at');
        });

        Schema::table('teaching_sessions', function (Blueprint $table) {
            $table->foreignId('program_chapter_id')->nullable()->after('pedagogical_assignment_id')->constrained()->nullOnDelete();
            $table->index('program_chapter_id');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_chapter_id');
        });

        Schema::table('program_chapters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_period_id');
            $table->dropColumn(['planned_at', 'status']);
        });

        Schema::table('program_annuals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pedagogical_assignment_id');
            $table->dropConstrainedForeignId('academic_period_id');
        });
    }
};
