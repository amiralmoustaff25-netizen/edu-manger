<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GradeCalculationService;

function enrollStudentInClassroom(Classroom $classroom, SchoolYear $year): User
{
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'registration_fee_paid' => 0,
        'monthly_fee' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'school_year_id' => $year->id,
        'matricule' => 'EDU-'.$student->id,
        'status' => 'active',
    ]);

    return $student;
}

function assignMatiereToClassroom(Classroom $classroom, SchoolYear $year, Matiere $matiere): void
{
    PedagogicalAssignment::create([
        'teacher_id' => Teacher::factory()->create()->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'school_year_id' => $year->id,
        'volume_horaire_hebdo' => 2,
        'is_active' => true,
    ]);
}

beforeEach(function () {
    $this->service = app(GradeCalculationService::class);
    $this->year = SchoolYear::create(['year_string' => '2026-2027', 'is_active' => true]);
    $this->classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $this->year->id]);
});

test('the bulletin only lists subjects actually assigned to the student classroom, not every subject in the system', function () {
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 2]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);

    // Matière existant dans le système mais jamais affectée à cette classe (ex. matière
    // d'un autre cycle) : elle ne doit jamais apparaître sur le bulletin de cette classe.
    Matiere::factory()->create(['nom' => 'Philosophie', 'coefficient' => 3]);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 14, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    $subjectNames = collect($bulletin['subjects'])->pluck('matiere');
    expect($subjectNames)->toContain('Mathématiques');
    expect($subjectNames)->not->toContain('Philosophie');
});

test('a subject with no grade for the period does not drag down the general average', function () {
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 2]);
    $french = Matiere::factory()->create(['nom' => 'Français', 'coefficient' => 3]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);
    assignMatiereToClassroom($this->classroom, $this->year, $french);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    // Seule Mathématiques a une note ; Français n'a rien pour ce trimestre.
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 16, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    // Si Français (coef 3, moyenne 0) comptait dans le calcul, la moyenne générale
    // serait (16*2 + 0*3) / 5 = 6.4 au lieu de 16 (seule matière notée).
    expect($bulletin['general_average'])->toBe(16.0);
    expect($bulletin['total_coefficients'])->toBe(2.0);
});

test('the general average is correctly weighted across multiple graded subjects', function () {
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 2]);
    $french = Matiere::factory()->create(['nom' => 'Français', 'coefficient' => 3]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);
    assignMatiereToClassroom($this->classroom, $this->year, $french);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 10, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $french->id, 'valeur' => 15, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    // (10*2 + 15*3) / (2+3) = 65/5 = 13
    expect($bulletin['general_average'])->toBe(13.0);
});

test('the class rank is consistent with the general average shown on the bulletin', function () {
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 1]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);

    $topStudent = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $topStudent->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 18, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $secondStudent = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $secondStudent->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 12, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $topBulletin = $this->service->getBulletinData($topStudent, 'trimestre_1');
    $secondBulletin = $this->service->getBulletinData($secondStudent, 'trimestre_1');

    expect($topBulletin['general_average'])->toBeGreaterThan($secondBulletin['general_average']);
    expect($topBulletin['rank'])->toBe(1);
    expect($secondBulletin['rank'])->toBe(2);
});
