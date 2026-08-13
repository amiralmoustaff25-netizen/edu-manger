<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a staff user can view and update their own profile', function () {
    $user = User::factory()->create(['role' => 'admin', 'name' => 'Ancien Nom']);
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertSee('Ancien Nom');
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nouveau Nom',
            'email' => $user->email,
        ])
        ->assertRedirect(route('profile.show'));

    expect($user->refresh()->name)->toBe('Nouveau Nom');
});

test('a profile photo can be uploaded and replaces the previous one', function () {
    // UploadedFile::fake()->image() nécessite l'extension GD (absente de cet
    // environnement) : on construit un vrai fichier PNG 1x1 minimal à la place,
    // pour exercer la validation MIME réelle sans dépendre de GD.
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $user->assignRole('admin');

    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    $tmpPath = tempnam(sys_get_temp_dir(), 'avatar').'.png';
    file_put_contents($tmpPath, $pngContent);
    $photo = new \Illuminate\Http\UploadedFile($tmpPath, 'avatar.png', 'image/png', null, true);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo' => $photo,
    ])->assertRedirect(route('profile.show'));

    $path = $user->refresh()->profile_photo_path;
    expect($path)->not->toBeNull();
    // SEC-03 : la photo de profil est désormais stockée sur le disque privé
    // `local`, jamais `public`, et servie uniquement via une route contrôlée.
    Storage::disk('local')->assertExists($path);
});

test('an eleve cannot edit their profile but can view it', function () {
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');

    $this->actingAs($student)->get(route('profile.show'))->assertOk();
    $this->actingAs($student)->get(route('profile.edit'))->assertForbidden();
    $this->actingAs($student)->patch(route('profile.update'), ['name' => 'Hack', 'email' => $student->email])->assertForbidden();
});

test('the password change modal on the profile page actually submits to the right route', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('profile.show'))
        ->assertSee('action="'.route('password.update').'"', false);
});

test('the delete account button is visible for staff but hidden for eleve', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');

    $this->actingAs($admin)->get(route('profile.show'))
        ->assertSee('action="'.route('profile.destroy').'"', false);

    $this->actingAs($student)->get(route('profile.show'))
        ->assertDontSee('action="'.route('profile.destroy').'"', false);
});

test('a user can delete their own account with the correct password', function () {
    $user = User::factory()->create(['role' => 'comptable', 'password' => Hash::make('password')]);
    $user->assignRole('comptable');

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    expect(User::withTrashed()->find($user->id)->trashed())->toBeTrue();
});

test('deleting the account requires the correct current password', function () {
    $user = User::factory()->create(['role' => 'comptable', 'password' => Hash::make('password')]);
    $user->assignRole('comptable');

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrorsIn('userDeletion', 'password');

    $this->assertAuthenticated();
    expect(User::find($user->id))->not->toBeNull();
});

test('an eleve cannot delete their own account', function () {
    $student = User::factory()->create(['role' => 'eleve', 'password' => Hash::make('password')]);
    $student->assignRole('eleve');

    $this->actingAs($student)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertForbidden();

    expect(User::find($student->id))->not->toBeNull();
});

test('the last active super-admin cannot delete their own account', function () {
    $superAdmin = User::factory()->create(['password' => Hash::make('password')]);
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertStatus(422);

    $this->assertAuthenticated();
    expect(User::find($superAdmin->id))->not->toBeNull();
});

test('a super-admin can delete their own account if another active super-admin remains', function () {
    $otherSuperAdmin = User::factory()->create(['is_active' => true]);
    $otherSuperAdmin->assignRole('super-admin');
    $superAdmin = User::factory()->create(['password' => Hash::make('password')]);
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    expect(User::withTrashed()->find($superAdmin->id)->trashed())->toBeTrue();
});
