<?php

use App\Models\User;

it('displays the student profile photo on the dashboard when set', function () {
    $student = User::factory()->create([
        'role' => 'eleve',
        'profile_photo_path' => 'photos/eleves/test.jpg',
    ])->assignRole('eleve');

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        // SEC-03 : la photo est désormais servie via une route contrôlée
        // (disque privé) plutôt que par une URL de stockage public directe.
        ->assertSee(route('students.photo', $student->id));
});

it('falls back to initials when no profile photo is set', function () {
    $student = User::factory()->create([
        'role' => 'eleve',
        'profile_photo_path' => null,
    ])->assignRole('eleve');

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee(mb_substr($student->name, 0, 1));
});
