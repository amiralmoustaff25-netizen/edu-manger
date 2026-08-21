<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\ParentModel;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;

/**
 * Tableau de bord de consultation des notes (StudentNotesController), réutilisé par
 * les profils Élève (ses propres notes) et Parent (notes d'un enfant lié) — lecture
 * seule, groupé par période selon le cycle (trimestres en primaire, semestres en
 * collège/lycée, voir App\Support\AcademicPeriods).
 */
function createNotesConsultationFixture(string $cycle): array
{
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id, 'cycle' => $cycle]);
    $matiere = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 2]);

    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id, 'volume_horaire_hebdo' => 4, 'is_active' => true,
    ]);

    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    Registration::create([
        'user_id' => $student->id, 'classroom_id' => $classroom->id, 'school_year_id' => $schoolYear->id,
        'registration_fee_paid' => 0, 'monthly_fee' => 0, 'registration_date' => now()->toDateString(),
        'academic_year' => $schoolYear->year_string, 'matricule' => 'ELE-'.$student->id, 'status' => 'active',
    ]);

    return [$student, $classroom, $matiere, $schoolYear];
}

test('a student sees their notes grouped by trimestre in primaire', function () {
    [$student, $classroom, $matiere] = createNotesConsultationFixture('primaire');
    Note::create(['user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id, 'valeur' => 15, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $this->actingAs($student)->get(route('student.notes'))
        ->assertOk()
        ->assertSee('Trimestre 1')
        ->assertSee('Trimestre 2')
        ->assertDontSee('Semestre')
        ->assertSee('Mathématiques')
        ->assertSee('Aucune note disponible');
});

test('a student sees their notes grouped by semestre with the weighted average in collège', function () {
    [$student, $classroom, $matiere] = createNotesConsultationFixture('college');
    Note::create(['user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id, 'valeur' => 11, 'type_evaluation' => 'devoir', 'evaluation_number' => 1, 'periode' => 'semestre_1']);
    Note::create(['user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id, 'valeur' => 17, 'type_evaluation' => 'devoir', 'evaluation_number' => 2, 'periode' => 'semestre_1']);
    Note::create(['user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id, 'valeur' => 13, 'type_evaluation' => 'composition', 'periode' => 'semestre_1']);

    $this->actingAs($student)->get(route('student.notes'))
        ->assertOk()
        ->assertSee('Semestre 1')
        ->assertSee('Semestre 2')
        ->assertDontSee('Trimestre')
        // (11+17)/2=14, puis (14+13)/2=13,5 — exemple exact du cahier des charges.
        ->assertSee('13,50');
});

test('a parent linked to the student can view their notes', function () {
    [$student] = createNotesConsultationFixture('primaire');

    $parentUser = User::factory()->create(['role' => 'parent']);
    $parentUser->assignRole('parent');
    $parentProfile = ParentModel::factory()->create(['user_id' => $parentUser->id, 'statut' => 'actif']);
    $parentProfile->students()->attach($student->id, ['lien_parente' => 'Pere']);

    $this->actingAs($parentUser)->get(route('parents.children.notes', ['student' => $student->id]))
        ->assertOk()
        ->assertSee($student->name);
});

test('a parent not linked to the student cannot view their notes', function () {
    [$student] = createNotesConsultationFixture('primaire');

    $unrelatedParentUser = User::factory()->create(['role' => 'parent']);
    $unrelatedParentUser->assignRole('parent');
    ParentModel::factory()->create(['user_id' => $unrelatedParentUser->id, 'statut' => 'actif']);

    $this->actingAs($unrelatedParentUser)->get(route('parents.children.notes', ['student' => $student->id]))
        ->assertRedirect(route('parents.dashboard'));
});
