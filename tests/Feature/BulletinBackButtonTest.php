<?php

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

it('student bulletin back button points to the student bulletins page', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    $student = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'active',
    ]);

    $this->actingAs($student)
        ->get(route('bulletins.show', [$student, 'trimestre_1']))
        ->assertOk()
        ->assertSee(route('student.bulletins'));
});

it('staff bulletin back button points to the bulletins index page', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    $student = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'active',
    ]);

    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');

    $this->actingAs($admin)
        ->get(route('bulletins.show', [$student, 'trimestre_1']))
        ->assertOk()
        ->assertSee(route('bulletins.index'));
});
