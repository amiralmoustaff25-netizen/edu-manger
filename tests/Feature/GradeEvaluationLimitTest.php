<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;

/**
 * "2 devoirs maximum par matière et par semestre" (cahier des charges, config
 * edu.max_evaluations_per_period) — collège/lycée uniquement. Rendu possible par
 * l'ajout de Note.evaluation_number (voir migration
 * 2026_08_21_120000_add_evaluation_number_to_notes_table), la contrainte unique
 * précédente n'autorisant qu'une seule note "devoir" par élève/matière/période.
 */
function createEvaluationLimitFixture(string $cycle = 'college'): array
{
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id, 'cycle' => $cycle]);
    $matiere = Matiere::factory()->create(['coefficient' => 3]);

    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);

    $student = User::factory()->create(['role' => 'eleve']);
    Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'active',
    ]);

    return [$teacherUser, $classroom, $matiere, $student];
}

test('a teacher can save a 2nd devoir for the same subject and period in collège', function () {
    [$teacherUser, $classroom, $matiere, $student] = createEvaluationLimitFixture('college');

    foreach ([1, 2] as $number) {
        $this->actingAs($teacherUser)->post(route('professeur.notes.store'), [
            'classroom_id' => $classroom->id,
            'matiere_id' => $matiere->id,
            'type_evaluation' => 'devoir',
            'evaluation_number' => $number,
            'periode' => 'semestre_1',
            'grades' => [
                ['user_id' => $student->id, 'valeur' => 10 + $number, 'appreciation' => null],
            ],
        ])->assertRedirect();
    }

    expect(Note::where('user_id', $student->id)->where('type_evaluation', 'devoir')->count())->toBe(2);
});

test('a 3rd devoir for the same subject and period is rejected in collège', function () {
    [$teacherUser, $classroom, $matiere, $student] = createEvaluationLimitFixture('college');

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.store'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'devoir',
        'evaluation_number' => 3,
        'periode' => 'semestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 12, 'appreciation' => null],
        ],
    ]);

    $response->assertStatus(422);
    expect(Note::where('user_id', $student->id)->where('type_evaluation', 'devoir')->count())->toBe(0);
});

test('a 2nd composition for the same subject and period is rejected in lycée', function () {
    [$teacherUser, $classroom, $matiere, $student] = createEvaluationLimitFixture('lycee');

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.store'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'evaluation_number' => 2,
        'periode' => 'semestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 12, 'appreciation' => null],
        ],
    ]);

    $response->assertStatus(422);
});

test('the evaluation number is always forced to 1 in primaire regardless of what is submitted', function () {
    [$teacherUser, $classroom, $matiere, $student] = createEvaluationLimitFixture('primaire');

    $this->actingAs($teacherUser)->post(route('professeur.notes.store'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'evaluation_number' => 2,
        'periode' => 'trimestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 12, 'appreciation' => null],
        ],
    ])->assertRedirect();

    $note = Note::where('user_id', $student->id)->first();
    expect($note->evaluation_number)->toBe(1);
});
