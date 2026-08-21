<?php

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\StudentClassHistory;
use App\Models\StudentDocument;
use App\Models\Teacher;
use App\Models\User;
use App\Support\StudentDocumentType;
use App\Support\StudentStatus;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->admin->assignRole('admin');
});

function studentFixture(string $status = 'active'): array
{
    $year = SchoolYear::firstOrCreate(['year_string' => '2026-2027'], ['is_active' => true]);

    $classroom = Classroom::create([
        'name' => 'CM2 B',
        'cycle' => 'primaire',
        'school_year_id' => $year->id,
    ]);

    $student = User::factory()->create([
        'matricule' => 'ELE-GUARD-'.uniqid(),
        'name' => 'Guard Test',
        'role' => 'eleve',
        'is_active' => $status === 'active',
    ]);
    $student->assignRole('eleve');

    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'registration_fee_paid' => 25000,
        'monthly_fee' => 15000,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'school_year_id' => $year->id,
        'matricule' => 'EDU-GUARD-'.uniqid(),
        'status' => $status,
    ]);

    return [$year, $classroom, $student, $registration];
}

test('enrolling a student generates a random temporary password, not the literal "password" (H1)', function () {
    [$year, $classroom] = studentFixture();

    $this->actingAs($this->admin)->post(route('registrations.store'), [
        'nom' => 'Nouveau',
        'prenom' => 'Eleve',
        'email' => 'nouvel.eleve@edumanager.sn',
        'date_naissance' => '2015-01-01',
        'lieu_naissance' => 'Dakar',
        'sexe' => 'M',
        'cycle' => 'primaire',
        'classroom_id' => $classroom->id,
        'role' => 'eleve',
        'is_active' => '1',
        'registration_fee_paid' => 10000,
        'monthly_fee' => 15000,
    ])->assertRedirect(route('dashboard'));

    $student = User::where('email', 'nouvel.eleve@edumanager.sn')->firstOrFail();

    expect(Hash::check('password', $student->password))->toBeFalse();
    expect($student->password_must_change)->toBeTrue();
});

test('an archived student can be restored from the listing (H2)', function () {
    [, , $student] = studentFixture();

    $this->actingAs($this->admin)->delete(route('students.destroy', $student))->assertRedirect();
    expect($student->fresh()->trashed())->toBeTrue();

    $this->actingAs($this->admin)
        ->post(route('students.restore', $student->id))
        ->assertRedirect(route('students.index'));

    expect(User::find($student->id)->trashed())->toBeFalse();
});

test('the archived students filter lists soft deleted accounts with a restore action', function () {
    [, , $student] = studentFixture();
    $student->delete();

    $this->actingAs($this->admin)
        ->get(route('students.index', ['status' => 'archived']))
        ->assertOk()
        ->assertSee($student->matricule)
        ->assertSee('Restaurer');
});

test('archiving a student with an active registration withdraws the registration instead of leaving it active (H3)', function () {
    [, , $student, $registration] = studentFixture('active');

    $this->actingAs($this->admin)->delete(route('students.destroy', $student))->assertRedirect();

    expect($registration->fresh()->status)->toBe(StudentStatus::WITHDRAWN);
    expect(User::withTrashed()->find($student->id)->is_active)->toBeFalse();
});

test('archiving a student with a pending registration cancels it instead of leaving it pending (H3)', function () {
    [, , $student, $registration] = studentFixture('pending');

    $this->actingAs($this->admin)->delete(route('students.destroy', $student))->assertRedirect();

    expect($registration->fresh()->status)->toBe(StudentStatus::CANCELLED);
});

test('a teacher not assigned to the student classroom cannot download the student documents (H6)', function () {
    Storage::fake('local');
    [, $classroom, $student] = studentFixture();

    $document = StudentDocument::create([
        'user_id' => $student->id,
        'type' => StudentDocumentType::values()[0],
        'original_filename' => 'test.pdf',
        'path' => 'student-documents/'.$student->id.'/test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->admin->id,
    ]);
    Storage::disk('local')->put($document->path, 'fake content');

    $teacher = Teacher::factory()->create();
    $teacher->user->assignRole('professeur');
    $teacher->user->update(['role' => 'professeur']);
    // Ce professeur n'est affecté à aucune classe : pas d'affectation pédagogique
    // créée pour lui sur $classroom.

    $this->actingAs($teacher->user)
        ->get(route('students.documents.download', [$student, $document]))
        ->assertForbidden();
});

test('a teacher assigned to the student classroom can download the student documents (H6)', function () {
    Storage::fake('local');
    [$year, $classroom, $student] = studentFixture();

    $document = StudentDocument::create([
        'user_id' => $student->id,
        'type' => StudentDocumentType::values()[0],
        'original_filename' => 'test.pdf',
        'path' => 'student-documents/'.$student->id.'/test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->admin->id,
    ]);
    Storage::disk('local')->put($document->path, 'fake content');

    $teacher = Teacher::factory()->create();
    $teacher->user->assignRole('professeur');
    $teacher->user->update(['role' => 'professeur']);

    // isTeacherAssignedToStudent() (comme l'ancien ensureTeacherIsAssignedToStudent()
    // qu'il remplace) vérifie Teacher::classrooms() — le pivot 'teacher_classroom' de
    // la gestion multi-enseignants — pas PedagogicalAssignment, qui gère un périmètre
    // fonctionnel différent (programmes, notes).
    $teacher->classrooms()->attach($classroom->id, ['annee_scolaire' => $year->year_string]);

    $this->actingAs($teacher->user)
        ->get(route('students.documents.download', [$student, $document]))
        ->assertOk();
});

test('a comptable can no longer reach the full student listing, but still reaches a single student profile (M2)', function () {
    $comptable = User::factory()->create(['role' => 'comptable', 'is_active' => true]);
    $comptable->assignRole('comptable');
    [, , $student] = studentFixture();

    $this->actingAs($comptable)->get(route('students.index'))->assertForbidden();
    $this->actingAs($comptable)->get(route('students.show', $student))->assertOk();
});

test('transferring a student records the previous classroom in the class history (M3)', function () {
    [$year, $classroom, $student, $registration] = studentFixture();
    $newClassroom = Classroom::create(['name' => 'CM2 C', 'cycle' => 'primaire', 'school_year_id' => $year->id]);

    $this->actingAs($this->admin)
        ->patch(route('students.transfer', $student), [
            'registration_id' => $registration->id,
            'classroom_id' => $newClassroom->id,
        ])
        ->assertRedirect();

    expect(StudentClassHistory::where('user_id', $student->id)->where('classroom_id', $classroom->id)->exists())->toBeTrue();
    expect($registration->fresh()->classroom_id)->toBe($newClassroom->id);
});

test('editing a student saves emergency contact and medical fields (M4)', function () {
    [, $classroom, $student] = studentFixture();

    $this->actingAs($this->admin)
        ->put(route('students.update', $student), [
            'nom' => 'Guard',
            'prenom' => 'Test',
            'email' => $student->email ?? 'guard.test@edumanager.sn',
            'date_naissance' => '2015-01-01',
            'lieu_naissance' => 'Dakar',
            'sexe' => 'M',
            'cycle' => 'primaire',
            'classroom_id' => $classroom->id,
            'emergency_contact_name' => 'Marie Diop',
            'emergency_contact_phone' => '770000000',
            'medical_notes' => 'Asthme',
            'allergies' => 'Arachides',
        ])
        ->assertRedirect(route('students.show', $student));

    $student->refresh();
    expect($student->emergency_contact_name)->toBe('Marie Diop');
    expect($student->emergency_contact_phone)->toBe('770000000');
    expect($student->medical_notes)->toBe('Asthme');
    expect($student->allergies)->toBe('Arachides');
});

test('editing a student with more than two linked parents does not silently drop the third link (H5)', function () {
    [, $classroom, $student] = studentFixture();

    $parents = ParentModel::factory()->count(3)->create(['statut' => 'actif']);
    foreach ($parents as $p) {
        $student->parents()->attach($p->id, ['lien_parente' => 'Tuteur', 'est_responsable_financier' => false, 'est_contact_urgence' => false]);
    }

    $editPage = $this->actingAs($this->admin)->get(route('students.edit', $student));
    $editPage->assertOk();

    // Simule exactement ce que le formulaire soumettrait : un emplacement par parent lié.
    $parentsPayload = [];
    foreach ($student->parents as $i => $p) {
        $parentsPayload[$i] = ['parent_id' => $p->id, 'lien_parente' => 'Tuteur'];
    }

    $this->actingAs($this->admin)
        ->put(route('students.update', $student), [
            'nom' => 'Guard',
            'prenom' => 'Test',
            'email' => $student->email ?? 'guard.test2@edumanager.sn',
            'date_naissance' => '2015-01-01',
            'lieu_naissance' => 'Dakar',
            'sexe' => 'M',
            'cycle' => 'primaire',
            'classroom_id' => $classroom->id,
            'parents' => $parentsPayload,
        ])
        ->assertRedirect(route('students.show', $student));

    expect($student->parents()->count())->toBe(3);
});
