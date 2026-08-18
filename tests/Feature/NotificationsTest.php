<?php

use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Services\AnnouncementService;

it('allows a super admin to publish an announcement to specific users', function () {
    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');
    $target = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    $other = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');

    $this->actingAs($admin);

    $response = $this->post(route('announcements.store'), [
        'title' => 'Réunion parents',
        'content' => 'Réunion parents le samedi 10 juin.',
        'type' => 'information',
        'priority' => 'normal',
        'target_mode' => 'users',
        'target_user_ids' => [$target->id],
        'action' => 'publish',
    ]);

    $response->assertRedirect(route('announcements.index'));

    $this->assertDatabaseHas('announcements', [
        'title' => 'Réunion parents',
        'status' => 'published',
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $target->id,
        'type' => AnnouncementPublished::class,
    ]);

    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $other->id,
        'type' => AnnouncementPublished::class,
    ]);
});

it('isolates classroom targeted announcements by role', function () {
    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);

    $student = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    \App\Models\Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'status' => 'active',
    ]);

    $otherClassStudent = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    $otherClassroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    \App\Models\Registration::factory()->create([
        'user_id' => $otherClassStudent->id,
        'classroom_id' => $otherClassroom->id,
        'status' => 'active',
    ]);

    $announcement = Announcement::create([
        'title' => 'Information classe',
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

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $student->id,
        'type' => AnnouncementPublished::class,
    ]);

    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $otherClassStudent->id,
        'type' => AnnouncementPublished::class,
    ]);
});

it('notifies parents (not just students/teachers) on a classroom announcement with no explicit role restriction', function () {
    // Régression Phase 2 / Checkpoint "audit complet" (finding C1) : AnnouncementService
    // n'importait pas ParentModel, ce qui faisait planter (500) toute annonce ciblée par
    // classe dès que le rôle "parent" faisait partie du ciblage — le cas par défaut quand
    // target_roles est vide (voir AnnouncementService::resolveByClassroom()).
    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);

    $student = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    \App\Models\Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'status' => 'active',
    ]);

    $parentUser = User::factory()->create(['role' => 'parent', 'is_active' => true])->assignRole('parent');
    $parent = \App\Models\ParentModel::factory()->create(['user_id' => $parentUser->id]);
    $parent->students()->attach($student->id);

    $announcement = Announcement::create([
        'title' => 'Information classe',
        'content' => 'Message pour la classe, tous destinataires par défaut.',
        'type' => 'information',
        'priority' => 'normal',
        'target_mode' => 'classroom',
        'target_roles' => [],
        'classroom_id' => $classroom->id,
        'status' => 'draft',
        'created_by' => $admin->id,
    ]);

    app(AnnouncementService::class)->publish($announcement);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $student->id,
        'type' => AnnouncementPublished::class,
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $parentUser->id,
        'type' => AnnouncementPublished::class,
    ]);
});

it('prevents students from creating announcements', function () {
    $student = User::factory()->create(['role' => 'eleve'])->assignRole('eleve');

    $this->actingAs($student)
        ->post(route('announcements.store'), [
            'title' => 'Test',
            'content' => 'Test',
            'type' => 'information',
            'priority' => 'normal',
            'target_mode' => 'all',
            'action' => 'publish',
        ])
        ->assertForbidden();
});

it('allows a user to mark notifications as read', function () {
    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');
    $user = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');

    $this->actingAs($admin);
    $this->post(route('announcements.store'), [
        'title' => 'Info',
        'content' => 'Contenu',
        'type' => 'information',
        'priority' => 'normal',
        'target_mode' => 'users',
        'target_user_ids' => [$user->id],
        'action' => 'publish',
    ]);

    $notification = $user->notifications()->first();
    expect($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->post(route('notifications.mark-as-read', $notification))
        ->assertRedirect();

    $notification->refresh();
    expect($notification->read_at)->not->toBeNull();
});

it('blocks users from viewing other users notifications', function () {
    $userA = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    $userB = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');

    $notification = $userA->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => AnnouncementPublished::class,
        'data' => ['title' => 'Test'],
    ]);

    $this->actingAs($userB)
        ->get(route('notifications.show', $notification))
        ->assertForbidden();
});

it('publishes scheduled announcements via the command', function () {
    $admin = User::factory()->create(['role' => 'super-admin'])->assignRole('super-admin');
    $user = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');

    $announcement = Announcement::create([
        'title' => 'Rappel programmé',
        'content' => 'Ceci est un rappel.',
        'type' => 'reminder',
        'priority' => 'normal',
        'target_mode' => 'users',
        'target_user_ids' => [$user->id],
        'status' => 'scheduled',
        'published_at' => now()->subMinute(),
        'created_by' => $admin->id,
    ]);

    $this->artisan('announcements:publish-scheduled')->assertSuccessful();

    $announcement->refresh();
    expect($announcement->status)->toBe('published');

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
        'type' => AnnouncementPublished::class,
    ]);
});
