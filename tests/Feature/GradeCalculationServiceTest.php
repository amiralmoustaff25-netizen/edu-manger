<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\SubjectConfiguration;
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

test('a coefficient configured via "Configuration pédagogique" overrides the global Matiere coefficient', function () {
    // L'écran "Matières & coefficients" (SubjectConfiguration) était jusqu'ici purement
    // décoratif : GradeCalculationService lisait toujours Matiere::coefficient (global,
    // non versionné), donc modifier un coefficient via cet écran n'avait aucun effet réel.
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 2]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);

    SubjectConfiguration::create([
        'matiere_id' => $math->id,
        'school_year_id' => $this->year->id,
        'cycle' => null,
        'classroom_id' => null,
        'coefficient' => 5,
        'is_active' => true,
    ]);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 14, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    expect($bulletin['subjects'][0]['coefficient'])->toBe(5.0);
    expect($bulletin['total_coefficients'])->toBe(5.0);
});

test('a cycle-specific configured coefficient takes priority over the "all cycles" configured coefficient', function () {
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 2]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);

    SubjectConfiguration::create([
        'matiere_id' => $math->id, 'school_year_id' => $this->year->id,
        'cycle' => null, 'classroom_id' => null, 'coefficient' => 5, 'is_active' => true,
    ]);
    // La classe de test est en cycle "primaire" (voir beforeEach) : ce coefficient
    // spécifique doit primer sur le coefficient "Tous les cycles" ci-dessus.
    SubjectConfiguration::create([
        'matiere_id' => $math->id, 'school_year_id' => $this->year->id,
        'cycle' => 'primaire', 'classroom_id' => null, 'coefficient' => 4, 'is_active' => true,
    ]);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 14, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    expect($bulletin['subjects'][0]['coefficient'])->toBe(4.0);
});

test('an inactive configured coefficient is ignored, falling back to the global Matiere coefficient', function () {
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 2]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);

    SubjectConfiguration::create([
        'matiere_id' => $math->id, 'school_year_id' => $this->year->id,
        'cycle' => null, 'classroom_id' => null, 'coefficient' => 5, 'is_active' => false,
    ]);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 14, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    expect($bulletin['subjects'][0]['coefficient'])->toBe(2.0);
});

test('a primaire classroom with configured barèmes computes the general average as points obtained over total barème, out of 20', function () {
    // Système "sunuBulletin" : Mathématiques /80, Arabe /10 — la moyenne générale n'est
    // ni une moyenne arithmétique ni pondérée par un petit coefficient classique, mais
    // somme des points obtenus / somme des barèmes × 20.
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 1]);
    $arabe = Matiere::factory()->create(['nom' => 'Arabe', 'coefficient' => 1]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);
    assignMatiereToClassroom($this->classroom, $this->year, $arabe);

    SubjectConfiguration::create(['matiere_id' => $math->id, 'school_year_id' => $this->year->id, 'cycle' => 'primaire', 'bareme' => 80, 'is_active' => true]);
    SubjectConfiguration::create(['matiere_id' => $arabe->id, 'school_year_id' => $this->year->id, 'cycle' => 'primaire', 'bareme' => 10, 'is_active' => true]);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 65, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $arabe->id, 'valeur' => 7, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    // (65 + 7) / (80 + 10) * 20 = 72/90*20 = 16
    expect($bulletin['general_average'])->toBe(16.0);
    expect($bulletin['total_coefficients'])->toBe(90.0);
    $subjectsByName = collect($bulletin['subjects'])->keyBy('matiere');
    expect($subjectsByName['Mathématiques']['bareme'])->toBe(80.0);
    expect($subjectsByName['Mathématiques']['weighted_average'])->toBe(65.0);
});

test('a primaire subject without a graded note this period is excluded from both sides of the barème average', function () {
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);
    $arabe = Matiere::factory()->create(['nom' => 'Arabe']);
    assignMatiereToClassroom($this->classroom, $this->year, $math);
    assignMatiereToClassroom($this->classroom, $this->year, $arabe);

    SubjectConfiguration::create(['matiere_id' => $math->id, 'school_year_id' => $this->year->id, 'cycle' => 'primaire', 'bareme' => 80, 'is_active' => true]);
    SubjectConfiguration::create(['matiere_id' => $arabe->id, 'school_year_id' => $this->year->id, 'cycle' => 'primaire', 'bareme' => 10, 'is_active' => true]);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    // Seule Mathématiques a une note ce trimestre.
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 40, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    // Si Arabe (barème 10, note 0) comptait, ce serait 40/90*20 = 8.89 au lieu de
    // 40/80*20 = 10 (seule matière notée, comme le principe déjà appliqué au collège/lycée).
    expect($bulletin['general_average'])->toBe(10.0);
});

test('a primaire classroom without any configured barème still falls back to the standard /20 system', function () {
    // usesBaremeSystem() n'active le système sunuBulletin que si l'établissement a
    // explicitement configuré au moins un barème — une classe de primaire non configurée
    // continue sur le système standard (coefficient=1 par défaut), sans changement de
    // comportement pour un établissement qui n'utilise pas ce système.
    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 1]);
    assignMatiereToClassroom($this->classroom, $this->year, $math);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $math->id, 'valeur' => 14, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    expect($bulletin['general_average'])->toBe(14.0);
});

test('resolveBareme falls back to 20 for a primaire subject with no configured barème, even when the barème system is active for other subjects', function () {
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);
    $unconfigured = Matiere::factory()->create(['nom' => 'Sport']);
    assignMatiereToClassroom($this->classroom, $this->year, $math);
    assignMatiereToClassroom($this->classroom, $this->year, $unconfigured);

    SubjectConfiguration::create(['matiere_id' => $math->id, 'school_year_id' => $this->year->id, 'cycle' => 'primaire', 'bareme' => 80, 'is_active' => true]);

    $student = enrollStudentInClassroom($this->classroom, $this->year);
    Note::create(['user_id' => $student->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $unconfigured->id, 'valeur' => 15, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $bulletin = $this->service->getBulletinData($student, 'trimestre_1');

    $sport = collect($bulletin['subjects'])->firstWhere('matiere', 'Sport');
    expect($sport['bareme'])->toBe(20.0);
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
