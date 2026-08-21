<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('teacher_classroom')
            ->join('classrooms', 'classrooms.id', '=', 'teacher_classroom.classroom_id')
            ->select('teacher_classroom.teacher_id', 'teacher_classroom.classroom_id', 'teacher_classroom.matiere_id', 'teacher_classroom.volume_horaire_hebdo', 'classrooms.school_year_id')
            ->whereNotNull('teacher_classroom.matiere_id')
            ->orderBy('teacher_classroom.id')
            ->each(function ($assignment) {
                DB::table('pedagogical_assignments')->updateOrInsert(
                    [
                        'teacher_id' => $assignment->teacher_id,
                        'classroom_id' => $assignment->classroom_id,
                        'matiere_id' => $assignment->matiere_id,
                        'school_year_id' => $assignment->school_year_id,
                    ],
                    [
                        'volume_horaire_hebdo' => $assignment->volume_horaire_hebdo,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });
    }

    public function down(): void {}
};
