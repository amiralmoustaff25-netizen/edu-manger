<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->user = User::factory()->create(['role' => 'professeur']);
    $this->user->assignRole('professeur');
    $this->teacher = Teacher::factory()->create(['user_id' => $this->user->id]);
    $this->schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $this->assignment = PedagogicalAssignment::create([
        'teacher_id' => $this->teacher->id,
        'classroom_id' => Classroom::factory()->create(['school_year_id' => $this->schoolYear->id])->id,
        'matiere_id' => Matiere::factory()->create()->id,
        'school_year_id' => $this->schoolYear->id,
        'volume_horaire_hebdo' => 4,
    ]);
    $this->actingAs($this->user);
});

test('teacher can point a course and see remaining weekly hours', function () {
    $this->post(route('professeur.teaching-sessions.store'), [
        'pedagogical_assignment_id' => $this->assignment->id,
        'taught_on' => now()->startOfWeek()->addDay()->toDateString(),
        'duration_hours' => 1.5,
        'summary' => 'Révision',
    ])->assertRedirect(route('professeur.teaching-sessions.index'));

    $this->get(route('professeur.teaching-sessions.index'))
        ->assertOk()
        ->assertSee('2.5 h restantes')
        ->assertSee('1.5 h réalisées');
});
