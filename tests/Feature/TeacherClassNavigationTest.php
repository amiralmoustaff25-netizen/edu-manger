<?php

use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->teacherUser = User::factory()->create(['role' => 'professeur']);
    $this->teacherUser->assignRole('professeur');
    $this->teacher = Teacher::factory()->create(['user_id' => $this->teacherUser->id]);
    $this->schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $this->classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
    $this->teacher->classrooms()->attach($this->classroom->id, [
        'annee_scolaire' => $this->schoolYear->year_string,
        'matiere_id' => null,
        'volume_horaire_hebdo' => 4,
    ]);

    $this->actingAs($this->teacherUser);
});

test('teacher can open a class detail page from mes classes', function () {
    $this->get(route('professeur.classes.index'))
        ->assertOk()
        ->assertSee(route('professeur.classes.show', $this->classroom), false);

    $this->get(route('professeur.classes.show', $this->classroom))
        ->assertOk()
        ->assertSee('Détails de la classe');
});

test('teacher can open the grade page for an assigned class', function () {
    $this->get(route('professeur.notes.index', ['classroom_id' => $this->classroom->id]))
        ->assertOk()
        ->assertSee('Saisie des Notes');
});
