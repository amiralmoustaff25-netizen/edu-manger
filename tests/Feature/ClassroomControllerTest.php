<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_all_classrooms(): void
    {
        Classroom::factory()->count(3)->create();

        $response = $this->get(route('classrooms.index'));

        $response->assertOk()
            ->assertViewIs('classrooms.index')
            ->assertViewHas('classrooms');
    }

    /** @test */
    public function the_classroom_list_no_longer_links_to_a_separate_teacher_management_page(): void
    {
        // La gestion des enseignants affectés a été intégrée à la page de modification de la
        // classe (classrooms.edit) : le lien "Enseignants" séparé dans la liste n'a plus de
        // raison d'exister.
        $classroom = Classroom::factory()->create();

        $response = $this->get(route('classrooms.index'));

        $response->assertOk()->assertDontSee('/classrooms/'.$classroom->id.'/teachers"', false);
    }

    /** @test */
    public function the_classroom_edit_page_now_exposes_teacher_management(): void
    {
        $classroom = Classroom::factory()->create();

        $response = $this->get(route('classrooms.edit', $classroom->id));

        $response->assertOk()
            ->assertViewIs('classrooms.edit')
            ->assertViewHas('teachers')
            ->assertViewHas('matieres')
            ->assertSee(route('classrooms.attach-teacher', $classroom->id), false);
    }

    /** @test */
    public function it_can_show_create_form(): void
    {
        $response = $this->get(route('classrooms.create'));

        $response->assertOk()
            ->assertViewIs('classrooms.create');
    }

    /** @test */
    public function it_can_store_a_new_classroom(): void
    {
        SchoolYear::factory()->create(['is_active' => true]);

        $response = $this->post(route('classrooms.store'), [
            'level' => 'CP',
            'section' => 'A',
            'max_students' => 30,
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseHas('classrooms', [
            'name' => 'CP A',
            'max_students' => 30,
        ]);
    }

    /** @test */
    public function it_stores_the_serie_for_a_lycee_classroom(): void
    {
        SchoolYear::factory()->create(['is_active' => true]);

        $response = $this->post(route('classrooms.store'), [
            'level' => 'Terminale',
            'section' => 'A',
            'serie' => 'S',
            'max_students' => 30,
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseHas('classrooms', [
            'name' => 'Terminale A',
            'cycle' => 'lycee',
            'serie' => 'S',
        ]);
    }

    /** @test */
    public function it_ignores_the_serie_for_a_non_lycee_classroom(): void
    {
        // La série n'a de sens qu'au lycée : même envoyée par erreur pour une classe de
        // primaire/collège, elle ne doit jamais être enregistrée.
        SchoolYear::factory()->create(['is_active' => true]);

        $this->post(route('classrooms.store'), [
            'level' => 'CP',
            'section' => 'A',
            'serie' => 'S',
            'max_students' => 30,
        ]);

        $this->assertDatabaseHas('classrooms', [
            'name' => 'CP A',
            'cycle' => 'primaire',
            'serie' => null,
        ]);
    }

    /** @test */
    public function assigning_a_titulaire_to_a_primaire_classroom_auto_creates_pedagogical_assignments(): void
    {
        // Pour le primaire, désigner l'enseignant titulaire (teacher_id) doit suffire : plus
        // besoin de dupliquer la même information dans "Affectations pédagogiques" pour que
        // le professeur principal couvre les matières générales de la classe.
        $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
        $teacher = Teacher::factory()->create();
        $teacher->user->assignRole('professeur');
        $matiere = Matiere::factory()->create(['nom' => 'Mathématiques']);

        $response = $this->post(route('classrooms.store'), [
            'level' => 'CP',
            'section' => 'A',
            'teacher_id' => $teacher->user_id,
            'max_students' => 30,
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $classroom = Classroom::where('name', 'CP A')->first();

        $this->assertDatabaseHas('pedagogical_assignments', [
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'matiere_id' => $matiere->id,
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function changing_the_titulaire_of_a_primaire_classroom_deactivates_the_previous_teachers_assignments(): void
    {
        $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
        $matiere = Matiere::factory()->create(['nom' => 'Mathématiques']);
        $oldTeacher = Teacher::factory()->create();
        $oldTeacher->user->assignRole('professeur');
        $newTeacher = Teacher::factory()->create();
        $newTeacher->user->assignRole('professeur');
        $classroom = Classroom::factory()->create(['cycle' => 'primaire', 'school_year_id' => $schoolYear->id, 'teacher_id' => $oldTeacher->user_id]);

        $oldAssignment = PedagogicalAssignment::create([
            'teacher_id' => $oldTeacher->id,
            'classroom_id' => $classroom->id,
            'matiere_id' => $matiere->id,
            'school_year_id' => $schoolYear->id,
            'volume_horaire_hebdo' => 0,
            'is_active' => true,
        ]);

        $this->put(route('classrooms.update', $classroom->id), [
            'level' => 'CP',
            'section' => explode(' ', $classroom->name)[1] ?? '',
            'teacher_id' => $newTeacher->user_id,
            'max_students' => 30,
        ]);

        $this->assertFalse($oldAssignment->refresh()->is_active);
        $this->assertDatabaseHas('pedagogical_assignments', [
            'classroom_id' => $classroom->id,
            'teacher_id' => $newTeacher->id,
            'matiere_id' => $matiere->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_store(): void
    {
        SchoolYear::factory()->create(['is_active' => true]);

        $response = $this->post(route('classrooms.store'), []);

        $response->assertSessionHasErrors(['level']);
    }

    /** @test */
    public function it_can_show_edit_form(): void
    {
        $classroom = Classroom::factory()->create();

        $response = $this->get(route('classrooms.edit', $classroom));

        $response->assertOk()
            ->assertViewIs('classrooms.edit')
            ->assertViewHas('classroom');
    }

    /** @test */
    public function it_can_update_a_classroom(): void
    {
        $classroom = Classroom::factory()->create();

        $response = $this->put(route('classrooms.update', $classroom), [
            'level' => 'CE1',
            'section' => 'B',
            'teacher_id' => null,
            'max_students' => 30,
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'name' => 'CE1 B',
        ]);
    }

    /** @test */
    public function it_can_delete_a_classroom_without_registrations(): void
    {
        $classroom = Classroom::factory()->create();

        $response = $this->delete(route('classrooms.destroy', $classroom));

        $response->assertRedirect(route('classrooms.index'));
        $this->assertSoftDeleted('classrooms', [
            'id' => $classroom->id,
        ]);
    }

    /** @test */
    public function it_blocks_deleting_a_classroom_with_registrations_even_for_an_admin(): void
    {
        $classroom = Classroom::factory()->create();
        $schoolYear = SchoolYear::factory()->create();
        $student = User::factory()->create();
        $student->assignRole('eleve');

        Registration::factory()->create([
            'user_id' => $student->id,
            'classroom_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
        ]);

        $response = $this->delete(route('classrooms.destroy', $classroom));

        $response->assertForbidden();
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_classroom(): void
    {
        $response = $this->get(route('classrooms.edit', 9999));

        $response->assertNotFound();
    }

    /** @test */
    public function it_can_filter_classrooms_by_niveau(): void
    {
        Classroom::factory()->count(2)->create(['cycle' => 'primaire']);
        Classroom::factory()->count(1)->create(['cycle' => 'college']);

        $response = $this->get(route('classrooms.index'));

        $response->assertOk()
            ->assertViewHas('classrooms', function ($classrooms) {
                return $classrooms->count() === 3;
            });
    }
}
