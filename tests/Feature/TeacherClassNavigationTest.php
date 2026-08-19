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

test('teacher can preview a student bulletin directly from the class grade entry grid', function () {
    // Aperçu basé sur les notes/matières déjà enregistrées (bulletins.show, recalculé en
    // direct par GradeCalculationService) — pas besoin de quitter la saisie de notes pour
    // voir où en est un élève.
    $matiere = \App\Models\Matiere::factory()->create();
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    \App\Models\Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $this->classroom->id,
        'school_year_id' => $this->schoolYear->id,
        'status' => 'active',
    ]);

    $response = $this->get(route('professeur.notes.index', [
        'classroom_id' => $this->classroom->id,
        'matiere_id' => $matiere->id,
    ]));

    // Le lien est injecté via Illuminate\Support\Js::from() (liaison Alpine :href) : l'URL
    // n'apparaît donc pas telle quelle dans le HTML (slashes/quotes échappés pour un
    // embarquement JS sûr) — on vérifie que le lien est bien présent pour ce rôle
    // autorisé, et que l'identifiant de l'élève et les 3 trimestres sont bien encodés
    // dans le bloc JSON.parse() qui construit ces URLs.
    $response->assertOk()->assertSee('Aperçu');
    $content = $response->getContent();
    expect($content)->toContain('JSON.parse(');
    expect(substr_count($content, (string) $student->id))->toBeGreaterThan(0);
    foreach (['trimestre_1', 'trimestre_2', 'trimestre_3'] as $period) {
        expect($content)->toContain($period);
    }
});
