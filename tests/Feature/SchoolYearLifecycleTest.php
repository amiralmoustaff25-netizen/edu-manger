<?php

namespace Tests\Feature;

use App\Models\SchoolYear;
use App\Models\User;
use App\Support\SchoolYearStatus;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SchoolYearLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function it_allows_the_documented_transitions(): void
    {
        $this->assertTrue(SchoolYearStatus::canTransition(SchoolYearStatus::PREPARATION, SchoolYearStatus::ACTIVE));
        $this->assertTrue(SchoolYearStatus::canTransition(SchoolYearStatus::ACTIVE, SchoolYearStatus::CLOSING));
        $this->assertTrue(SchoolYearStatus::canTransition(SchoolYearStatus::CLOSING, SchoolYearStatus::CLOSED));
        $this->assertTrue(SchoolYearStatus::canTransition(SchoolYearStatus::CLOSING, SchoolYearStatus::ACTIVE));
        $this->assertTrue(SchoolYearStatus::canTransition(SchoolYearStatus::CLOSED, SchoolYearStatus::ARCHIVED));
        $this->assertTrue(SchoolYearStatus::canTransition(SchoolYearStatus::CLOSED, SchoolYearStatus::ACTIVE));
    }

    /** @test */
    public function it_rejects_undocumented_transitions(): void
    {
        $this->assertFalse(SchoolYearStatus::canTransition(SchoolYearStatus::PREPARATION, SchoolYearStatus::CLOSED));
        $this->assertFalse(SchoolYearStatus::canTransition(SchoolYearStatus::ACTIVE, SchoolYearStatus::ARCHIVED));
        $this->assertFalse(SchoolYearStatus::canTransition(SchoolYearStatus::ARCHIVED, SchoolYearStatus::ACTIVE));
    }

    /** @test */
    public function transition_to_throws_a_validation_exception_for_an_invalid_transition(): void
    {
        $schoolYear = SchoolYear::factory()->create(['status' => SchoolYearStatus::PREPARATION]);

        $this->expectException(ValidationException::class);

        $schoolYear->transitionTo(SchoolYearStatus::CLOSED);
    }

    /** @test */
    public function transition_to_active_closes_the_previously_active_year(): void
    {
        $current = SchoolYear::factory()->create(['is_active' => true, 'status' => SchoolYearStatus::ACTIVE]);
        $next = SchoolYear::factory()->create(['is_active' => false, 'status' => SchoolYearStatus::PREPARATION]);

        $next->transitionTo(SchoolYearStatus::ACTIVE);

        $this->assertDatabaseHas('school_years', [
            'id' => $current->id,
            'is_active' => false,
            'status' => SchoolYearStatus::CLOSED,
        ]);
        $this->assertDatabaseHas('school_years', [
            'id' => $next->id,
            'is_active' => true,
            'status' => SchoolYearStatus::ACTIVE,
        ]);
    }

    /** @test */
    public function reactivating_a_closed_year_stamps_reopened_at_and_reopened_by(): void
    {
        $this->actingAs($this->admin);

        $schoolYear = SchoolYear::factory()->create(['is_active' => false, 'status' => SchoolYearStatus::CLOSED]);

        $schoolYear->transitionTo(SchoolYearStatus::ACTIVE);

        $schoolYear->refresh();
        $this->assertNotNull($schoolYear->reopened_at);
        $this->assertEquals($this->admin->id, $schoolYear->reopened_by);
    }

    /** @test */
    public function an_admin_can_update_a_school_year(): void
    {
        $this->actingAs($this->admin);
        $schoolYear = SchoolYear::factory()->create(['year_string' => '2030-2031']);

        $response = $this->put(route('school-years.update', $schoolYear), [
            'year_string' => '2030-2031',
            'start_date' => '2030-09-15',
            'end_date' => '2031-06-30',
        ]);

        $response->assertRedirect(route('school-years.index'));
        $this->assertEquals('2030-09-15', $schoolYear->fresh()->start_date->format('Y-m-d'));
    }

    /** @test */
    public function a_user_without_the_permission_cannot_update_a_school_year(): void
    {
        $user = User::factory()->create();
        $user->assignRole('professeur');
        $this->actingAs($user);

        $schoolYear = SchoolYear::factory()->create();

        $response = $this->put(route('school-years.update', $schoolYear), [
            'year_string' => $schoolYear->year_string,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function an_admin_can_view_the_closure_checklist(): void
    {
        $this->actingAs($this->admin);
        $schoolYear = SchoolYear::factory()->create(['is_active' => true, 'status' => SchoolYearStatus::ACTIVE]);

        $response = $this->get(route('school-years.closure-checklist', $schoolYear));

        $response->assertOk()
            ->assertViewIs('school_years.closure_checklist')
            ->assertViewHas('checklist', function ($checklist) {
                return array_key_exists('comptabilite', $checklist)
                    && array_key_exists('pedagogie', $checklist)
                    && array_key_exists('administration', $checklist);
            });
    }

    /** @test */
    public function a_user_without_the_permission_cannot_view_the_closure_checklist(): void
    {
        $user = User::factory()->create();
        $user->assignRole('professeur');
        $this->actingAs($user);

        $schoolYear = SchoolYear::factory()->create();

        $this->get(route('school-years.closure-checklist', $schoolYear))->assertForbidden();
    }

    /** @test */
    public function an_admin_can_start_and_cancel_a_closing(): void
    {
        $this->actingAs($this->admin);
        $schoolYear = SchoolYear::factory()->create(['is_active' => true, 'status' => SchoolYearStatus::ACTIVE]);

        $this->post(route('school-years.start-closing', $schoolYear))->assertRedirect();
        $this->assertDatabaseHas('school_years', ['id' => $schoolYear->id, 'status' => SchoolYearStatus::CLOSING]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'school_year_closing_started', 'model_id' => $schoolYear->id]);

        $this->post(route('school-years.cancel-closing', $schoolYear))->assertRedirect();
        $this->assertDatabaseHas('school_years', ['id' => $schoolYear->id, 'status' => SchoolYearStatus::ACTIVE]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'school_year_closing_cancelled', 'model_id' => $schoolYear->id]);
    }

    /** @test */
    public function a_user_without_the_permission_cannot_start_closing(): void
    {
        $user = User::factory()->create();
        $user->assignRole('professeur');
        $this->actingAs($user);

        $schoolYear = SchoolYear::factory()->create(['is_active' => true, 'status' => SchoolYearStatus::ACTIVE]);

        $this->post(route('school-years.start-closing', $schoolYear))->assertForbidden();
    }

    /** @test */
    public function activating_a_closed_school_year_is_refused_in_favor_of_the_reopen_flow(): void
    {
        $this->actingAs($this->admin);
        $schoolYear = SchoolYear::factory()->create(['is_active' => false, 'status' => SchoolYearStatus::CLOSED]);

        $response = $this->post(route('school-years.activate', $schoolYear));

        $response->assertRedirect();
        $this->assertDatabaseHas('school_years', ['id' => $schoolYear->id, 'status' => SchoolYearStatus::CLOSED]);
    }

    /** @test */
    public function a_super_admin_can_reopen_a_closed_school_year_with_the_correct_password(): void
    {
        $superAdmin = User::factory()->create(['password' => bcrypt('correct-password')]);
        $superAdmin->assignRole('super-admin');
        $this->actingAs($superAdmin);

        $schoolYear = SchoolYear::factory()->create(['is_active' => false, 'status' => SchoolYearStatus::CLOSED]);

        $response = $this->post(route('school-years.reopen', $schoolYear), ['password' => 'correct-password']);

        $response->assertRedirect(route('school-years.index'));
        // is_active reste false : la réouverture lève le verrou d'écriture (status) sans
        // faire de cette vieille année le contexte opérationnel actuel de l'établissement.
        $this->assertDatabaseHas('school_years', ['id' => $schoolYear->id, 'status' => SchoolYearStatus::ACTIVE, 'is_active' => false]);
        $this->assertFalse($schoolYear->fresh()->isLocked());
        $this->assertDatabaseHas('audit_logs', ['action' => 'school_year_reopened', 'model_id' => $schoolYear->id]);
    }

    /** @test */
    public function reopening_an_old_closed_year_does_not_affect_the_currently_active_year(): void
    {
        $superAdmin = User::factory()->create(['password' => bcrypt('correct-password')]);
        $superAdmin->assignRole('super-admin');
        $this->actingAs($superAdmin);

        $currentYear = SchoolYear::factory()->create(['is_active' => true, 'status' => SchoolYearStatus::ACTIVE, 'year_string' => '2026-2027']);
        $oldClosedYear = SchoolYear::factory()->create(['is_active' => false, 'status' => SchoolYearStatus::CLOSED, 'year_string' => '2020-2021']);

        $this->post(route('school-years.reopen', $oldClosedYear), ['password' => 'correct-password'])->assertRedirect();

        // La vraie année en cours ne doit jamais être touchée par la réouverture exceptionnelle
        // d'une année clôturée plus ancienne — bug constaté et corrigé pendant la sous-étape D.
        $this->assertDatabaseHas('school_years', ['id' => $currentYear->id, 'status' => SchoolYearStatus::ACTIVE, 'is_active' => true]);
    }

    /** @test */
    public function activating_a_reopened_year_that_is_not_yet_the_active_context_works(): void
    {
        $this->actingAs($this->admin);

        $currentYear = SchoolYear::factory()->create(['is_active' => true, 'status' => SchoolYearStatus::ACTIVE, 'year_string' => '2026-2027']);
        // Simule une année déjà rouverte exceptionnellement (status=active, is_active=false)
        // sans repasser par le flux de réouverture complet, pour isoler ce cas précis.
        $reopenedYear = SchoolYear::factory()->create(['is_active' => false, 'status' => SchoolYearStatus::ACTIVE, 'year_string' => '2020-2021']);

        $response = $this->post(route('school-years.activate', $reopenedYear));

        $response->assertRedirect();
        $this->assertDatabaseHas('school_years', ['id' => $reopenedYear->id, 'is_active' => true, 'status' => SchoolYearStatus::ACTIVE]);
        $this->assertDatabaseHas('school_years', ['id' => $currentYear->id, 'is_active' => false, 'status' => SchoolYearStatus::CLOSED]);
    }

    /** @test */
    public function reopening_with_the_wrong_password_fails(): void
    {
        $superAdmin = User::factory()->create(['password' => bcrypt('correct-password')]);
        $superAdmin->assignRole('super-admin');
        $this->actingAs($superAdmin);

        $schoolYear = SchoolYear::factory()->create(['is_active' => false, 'status' => SchoolYearStatus::CLOSED]);

        $response = $this->post(route('school-years.reopen', $schoolYear), ['password' => 'wrong-password']);

        $response->assertSessionHasErrorsIn('schoolYearReopen', ['password']);
        $this->assertDatabaseHas('school_years', ['id' => $schoolYear->id, 'status' => SchoolYearStatus::CLOSED]);
    }

    /** @test */
    public function an_admin_who_is_not_super_admin_cannot_reopen_a_closed_school_year(): void
    {
        $this->actingAs($this->admin);
        $schoolYear = SchoolYear::factory()->create(['is_active' => false, 'status' => SchoolYearStatus::CLOSED]);

        $this->post(route('school-years.reopen', $schoolYear), ['password' => 'whatever'])->assertForbidden();
        $this->get(route('school-years.reopen.show', $schoolYear))->assertForbidden();
    }

    /** @test */
    public function is_locked_is_true_only_for_closed_and_archived_years(): void
    {
        $this->assertFalse(SchoolYear::factory()->make(['status' => SchoolYearStatus::PREPARATION])->isLocked());
        $this->assertFalse(SchoolYear::factory()->make(['status' => SchoolYearStatus::ACTIVE])->isLocked());
        $this->assertFalse(SchoolYear::factory()->make(['status' => SchoolYearStatus::CLOSING])->isLocked());
        $this->assertTrue(SchoolYear::factory()->make(['status' => SchoolYearStatus::CLOSED])->isLocked());
        $this->assertTrue(SchoolYear::factory()->make(['status' => SchoolYearStatus::ARCHIVED])->isLocked());
    }

    /** @test */
    public function migrate_legacy_values_maps_old_status_strings_to_the_new_vocabulary(): void
    {
        // Simule des lignes créées avant l'extension du cycle de vie (contourne le modèle
        // pour insérer directement l'ancien vocabulaire, comme le ferait la base réelle
        // avant que la migration 2026_08_11_150000 ne s'exécute).
        DB::table('school_years')->insert([
            ['year_string' => '2019-2020', 'is_active' => false, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['year_string' => '2020-2021', 'is_active' => false, 'status' => 'upcoming', 'created_at' => now(), 'updated_at' => now()],
            ['year_string' => '2021-2022', 'is_active' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        SchoolYearStatus::migrateLegacyValues();

        $this->assertDatabaseHas('school_years', ['year_string' => '2019-2020', 'status' => SchoolYearStatus::CLOSED]);
        $this->assertDatabaseHas('school_years', ['year_string' => '2020-2021', 'status' => SchoolYearStatus::PREPARATION]);
        $this->assertDatabaseHas('school_years', ['year_string' => '2021-2022', 'status' => SchoolYearStatus::ACTIVE]);
    }
}
