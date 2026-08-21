<?php

use App\Models\Classroom;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Support\TimetableGrid;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $this->classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);
});

test('a super admin can view and save the timetable grid for a primaire classroom', function () {
    $admin = User::factory()->create(['role' => 'super-admin']);
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->get(route('timetable.edit', $this->classroom))
        ->assertOk()
        ->assertSee('Lundi')
        ->assertSee('08h/9h');

    $this->actingAs($admin)
        ->put(route('timetable.update', $this->classroom), [
            'content' => ['Lundi' => ['08h/9h' => 'Mathématiques'], 'Mardi' => ['9h/10h' => 'Récréation']],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('timetable_entries', [
        'classroom_id' => $this->classroom->id, 'school_year_id' => $this->schoolYear->id,
        'day' => 'Lundi', 'slot' => '08h/9h', 'content' => 'Mathématiques',
    ]);
    $this->assertDatabaseHas('timetable_entries', [
        'classroom_id' => $this->classroom->id, 'day' => 'Mardi', 'slot' => '9h/10h', 'content' => 'Récréation',
    ]);
    // Toutes les autres cases sont créées vides (content=null), pas absentes — la grille
    // reste complète (10 créneaux x 6 jours) même partiellement remplie.
    expect(TimetableEntry::where('classroom_id', $this->classroom->id)->count())->toBe(60);
});

test('the classroom titulaire teacher can manage the timetable of their own primaire classroom, but not of another one', function () {
    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $this->classroom->update(['teacher_id' => $teacherUser->id]);

    $otherClassroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);

    $this->actingAs($teacherUser)->get(route('timetable.edit', $this->classroom))->assertOk();
    $this->actingAs($teacherUser)->get(route('timetable.edit', $otherClassroom))->assertForbidden();
});

test('a teacher who is not the titulaire of a primaire classroom cannot manage its timetable, even with a subject assignment there', function () {
    // Un professeur d'anglais (matière spécialisée) affecté à la classe peut la CONSULTER
    // (voir test de authorizeView plus bas) mais pas la MODIFIER : seul le titulaire édite.
    $specialistTeacherUser = User::factory()->create(['role' => 'professeur']);
    $specialistTeacherUser->assignRole('professeur');
    $specialistTeacher = Teacher::factory()->create(['user_id' => $specialistTeacherUser->id]);
    $matiere = \App\Models\Matiere::factory()->create(['nom' => 'Anglais']);
    PedagogicalAssignment::create([
        'teacher_id' => $specialistTeacher->id, 'classroom_id' => $this->classroom->id, 'matiere_id' => $matiere->id,
        'school_year_id' => $this->schoolYear->id, 'volume_horaire_hebdo' => 0, 'is_active' => true,
    ]);

    $this->actingAs($specialistTeacherUser)->get(route('timetable.edit', $this->classroom))->assertForbidden();
});

test('a surveillant can manage the timetable of any primaire classroom', function () {
    $surveillant = User::factory()->create(['role' => 'surveillant']);
    $surveillant->assignRole('surveillant');

    $this->actingAs($surveillant)->get(route('timetable.edit', $this->classroom))->assertOk();
});

test('a comptable cannot access the timetable index or edit pages', function () {
    $comptable = User::factory()->create(['role' => 'comptable']);
    $comptable->assignRole('comptable');

    $this->actingAs($comptable)->get(route('timetable.index'))->assertForbidden();
    $this->actingAs($comptable)->get(route('timetable.edit', $this->classroom))->assertForbidden();
});

test('the timetable can be imported from an Excel file matching the grid shape', function () {
    $admin = User::factory()->create(['role' => 'super-admin']);
    $admin->assignRole('super-admin');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Horaire');
    foreach (TimetableGrid::DAYS as $i => $day) {
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2).'1', $day);
    }
    foreach (TimetableGrid::SLOTS as $i => $slot) {
        $sheet->setCellValue('A'.($i + 2), $slot);
    }
    // Lundi (colonne B), premier créneau (ligne 2) = "Français".
    $sheet->setCellValue('B2', 'Français');
    // Samedi (dernière colonne), dernier créneau (dernière ligne) = "Sport".
    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(TimetableGrid::DAYS) + 1);
    $lastRow = count(TimetableGrid::SLOTS) + 1;
    $sheet->setCellValue($lastCol.$lastRow, 'Sport');

    $tmpPath = tempnam(sys_get_temp_dir(), 'tt').'.xlsx';
    (new Xlsx($spreadsheet))->save($tmpPath);
    $file = new UploadedFile($tmpPath, 'emploi.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $this->actingAs($admin)
        ->post(route('timetable.import', $this->classroom), ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('timetable_entries', [
        'classroom_id' => $this->classroom->id, 'day' => TimetableGrid::DAYS[0], 'slot' => TimetableGrid::SLOTS[0], 'content' => 'Français',
    ]);
    $this->assertDatabaseHas('timetable_entries', [
        'classroom_id' => $this->classroom->id, 'day' => TimetableGrid::DAYS[array_key_last(TimetableGrid::DAYS)], 'slot' => TimetableGrid::SLOTS[array_key_last(TimetableGrid::SLOTS)], 'content' => 'Sport',
    ]);

    @unlink($tmpPath);
});

test('the printable PDF is accessible to the titulaire teacher and downloads correctly', function () {
    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $this->classroom->update(['teacher_id' => $teacherUser->id]);

    $response = $this->actingAs($teacherUser)->get(route('timetable.print', $this->classroom));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('a primaire classroom whose titulaire is set via teacher_id (but has no legacy teacher_classroom pivot) shows its timetable correctly to its students, instead of "no teacher associated"', function () {
    // Régression : students/timetable.blade.php lisait Classroom::teachers() (ancien pivot
    // teacher_classroom), jamais alimenté pour le primaire depuis que teacher_id (titulaire)
    // suffit — la page affichait "Aucun enseignant associé" malgré un titulaire bien défini.
    $teacherUser = User::factory()->create(['role' => 'professeur', 'name' => 'Cheikh Ndour']);
    $teacherUser->assignRole('professeur');
    $this->classroom->update(['teacher_id' => $teacherUser->id]);

    TimetableEntry::create([
        'classroom_id' => $this->classroom->id, 'school_year_id' => $this->schoolYear->id,
        'day' => 'Lundi', 'slot' => '08h/9h', 'content' => 'Mathématiques',
    ]);

    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    \App\Models\Registration::factory()->create([
        'user_id' => $student->id, 'classroom_id' => $this->classroom->id,
        'school_year_id' => $this->schoolYear->id, 'status' => 'active',
    ]);

    $response = $this->actingAs($student)->get(route('student.timetable'));

    $response->assertOk()
        ->assertDontSee('Aucun enseignant associé')
        ->assertSee('Mathématiques')
        ->assertSee('Lundi');
});
