<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
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
    $admin = User::factory()->create(['role' => 'admin']);
    // Le bulletin n'est visible côté élève qu'une fois la note validée par la direction
    // (décision produit 2026-08-29, voir Note::isPeriodPublishedFor()).
    Note::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => Matiere::factory()->create()->id,
        'valeur' => 15,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'validated_at' => now(),
        'validated_by' => $admin->id,
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
