<?php

use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Services\AnnouncementService;

it('shows notifications to a student on the notifications index', function () {
    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');
    $student = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');

    $this->actingAs($admin);

    $this->post(route('announcements.store'), [
        'title' => 'Info aux élèves',
        'content' => 'Message test',
        'type' => 'information',
        'priority' => 'normal',
        'target_mode' => 'users',
        'target_user_ids' => [$student->id],
        'action' => 'publish',
    ])->assertRedirect();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $student->id,
        'type' => AnnouncementPublished::class,
    ]);

    $this->actingAs($student)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Info aux élèves');
});

it('shows classroom targeted notifications to a student', function () {
    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);

    $student = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'status' => 'active',
    ]);

    $announcement = Announcement::create([
        'title' => 'Classe info',
        'content' => 'Message pour la classe.',
        'type' => 'information',
        'priority' => 'normal',
        'target_mode' => 'classroom',
        'target_roles' => ['eleve'],
        'classroom_id' => $classroom->id,
        'status' => 'draft',
        'created_by' => $admin->id,
    ]);

    app(AnnouncementService::class)->publish($announcement);

    $this->actingAs($student)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Classe info');
});

it('shows all-target notifications to a student', function () {
    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');
    $student = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');

    $this->actingAs($admin);

    $this->post(route('announcements.store'), [
        'title' => 'Info générale',
        'content' => 'Message pour tout le monde.',
        'type' => 'information',
        'priority' => 'normal',
        'target_mode' => 'all',
        'action' => 'publish',
    ])->assertRedirect();

    $this->actingAs($student)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Info générale');
});
