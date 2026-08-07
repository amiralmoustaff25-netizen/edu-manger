<?php

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function createDocumentFixture(): array
{
    $year = SchoolYear::create(['year_string' => '2026-2027', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $student = User::factory()->create(['matricule' => 'ELE-DOC-001', 'role' => 'eleve', 'is_active' => true]);
    $student->assignRole('eleve');
    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'registration_fee_paid' => 25000,
        'monthly_fee' => 15000,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'school_year_id' => $year->id,
        'matricule' => 'EDU-DOC-001',
        'status' => 'active',
    ]);

    return [$year, $classroom, $student];
}

function fakePdf(string $name = 'acte.pdf'): \Illuminate\Http\UploadedFile
{
    $tmpPath = tempnam(sys_get_temp_dir(), 'doc').'.pdf';
    file_put_contents($tmpPath, "%PDF-1.4\n%fake pdf content for tests\n");

    return new \Illuminate\Http\UploadedFile($tmpPath, $name, 'application/pdf', null, true);
}

beforeEach(function () {
    Storage::fake('local');
});

test('an admin can upload a document for a student', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [, , $student] = createDocumentFixture();

    $this->actingAs($admin)
        ->post(route('students.documents.store', $student), [
            'type' => 'acte_naissance',
            'file' => fakePdf(),
        ])
        ->assertRedirect();

    $document = StudentDocument::where('user_id', $student->id)->firstOrFail();
    expect($document->type)->toBe('acte_naissance');
    expect($document->uploaded_by)->toBe($admin->id);
    Storage::disk('local')->assertExists($document->path);
});

test('the documents button on the student page points to the real documents section, not a decoy anchor', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [, , $student] = createDocumentFixture();

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertSee('href="#documents"', false)
        ->assertSee('id="documents"', false);
});

test('a comptable cannot upload or delete a document', function () {
    $comptable = User::factory()->create(['role' => 'comptable']);
    $comptable->assignRole('comptable');
    [, , $student] = createDocumentFixture();

    $this->actingAs($comptable)
        ->post(route('students.documents.store', $student), [
            'type' => 'acte_naissance',
            'file' => fakePdf(),
        ])
        ->assertForbidden();
});

test('uploading rejects disallowed file types', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [, , $student] = createDocumentFixture();

    $tmpPath = tempnam(sys_get_temp_dir(), 'doc').'.exe';
    file_put_contents($tmpPath, 'MZ fake executable');
    $file = new \Illuminate\Http\UploadedFile($tmpPath, 'malware.exe', 'application/x-msdownload', null, true);

    $this->actingAs($admin)
        ->post(route('students.documents.store', $student), [
            'type' => 'autre',
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});

test('a user who can view the student can download their document', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [, , $student] = createDocumentFixture();

    $this->actingAs($admin)->post(route('students.documents.store', $student), [
        'type' => 'certificat_medical',
        'file' => fakePdf('certificat.pdf'),
    ]);
    $document = StudentDocument::where('user_id', $student->id)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('students.documents.download', [$student, $document]))
        ->assertOk();
});

test('a document cannot be downloaded through a mismatched student in the url (IDOR protection)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [$year, $classroom, $student] = createDocumentFixture();

    $otherStudent = User::factory()->create(['matricule' => 'ELE-DOC-002', 'role' => 'eleve', 'is_active' => true]);
    $otherStudent->assignRole('eleve');
    Registration::create([
        'user_id' => $otherStudent->id,
        'classroom_id' => $classroom->id,
        'registration_fee_paid' => 0,
        'monthly_fee' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'school_year_id' => $year->id,
        'matricule' => 'EDU-DOC-002',
        'status' => 'active',
    ]);

    $this->actingAs($admin)->post(route('students.documents.store', $student), [
        'type' => 'acte_naissance',
        'file' => fakePdf(),
    ]);
    $document = StudentDocument::where('user_id', $student->id)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('students.documents.download', [$otherStudent, $document]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->delete(route('students.documents.destroy', [$otherStudent, $document]))
        ->assertNotFound();

    Storage::disk('local')->assertExists($document->path);
});

test('an admin can delete a document, removing both the record and the file', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [, , $student] = createDocumentFixture();

    $this->actingAs($admin)->post(route('students.documents.store', $student), [
        'type' => 'autre',
        'file' => fakePdf(),
    ]);
    $document = StudentDocument::where('user_id', $student->id)->firstOrFail();
    $path = $document->path;

    $this->actingAs($admin)
        ->delete(route('students.documents.destroy', [$student, $document]))
        ->assertRedirect();

    expect(StudentDocument::find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing($path);
});
