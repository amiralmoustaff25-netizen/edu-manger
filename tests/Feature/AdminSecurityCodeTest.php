<?php

use App\Models\SchoolYear;
use App\Models\User;
use App\Services\AdminSecurityCodeService;

test('super-admin can set a security code after verifying their current password', function () {
    $superAdmin = User::factory()->create(['password' => bcrypt('correct-password')]);
    $superAdmin->assignRole('super-admin');

    $response = $this->actingAs($superAdmin)->put('/admin/security-code', [
        'current_password' => 'correct-password',
        'security_code' => 'a-secret-code',
        'security_code_confirmation' => 'a-secret-code',
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();
    expect(app(AdminSecurityCodeService::class)->hasCode($superAdmin->fresh()))->toBeTrue();
});

test('setting a security code fails with the wrong current password', function () {
    $superAdmin = User::factory()->create(['password' => bcrypt('correct-password')]);
    $superAdmin->assignRole('super-admin');

    $response = $this->actingAs($superAdmin)->put('/admin/security-code', [
        'current_password' => 'wrong-password',
        'security_code' => 'a-secret-code',
        'security_code_confirmation' => 'a-secret-code',
    ]);

    $response->assertSessionHasErrors('current_password');
    expect(app(AdminSecurityCodeService::class)->hasCode($superAdmin->fresh()))->toBeFalse();
});

test('admin cannot access the security code screen', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin/security-code');

    $response->assertForbidden();
});

test('the security code is never exposed in serialized user data', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    app(AdminSecurityCodeService::class)->setCode($superAdmin, 'a-secret-code');

    expect($superAdmin->fresh()->toArray())->not->toHaveKey('security_code');
});

test('deleting a school year is allowed without a security code when none is set', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $schoolYear = SchoolYear::factory()->create(['is_active' => false]);

    $response = $this->actingAs($superAdmin)->delete(route('school-years.destroy', $schoolYear));

    $response->assertRedirect()->assertSessionHasNoErrors();
    expect(SchoolYear::withTrashed()->find($schoolYear->id)->trashed())->toBeTrue();
});

test('deleting a school year is blocked without the correct security code once one is set', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    app(AdminSecurityCodeService::class)->setCode($superAdmin, 'a-secret-code');
    $schoolYear = SchoolYear::factory()->create(['is_active' => false]);

    $response = $this->actingAs($superAdmin)->delete(route('school-years.destroy', $schoolYear), [
        'security_code' => 'wrong-code',
    ]);

    $response->assertSessionHasErrors('security_code');
    expect(SchoolYear::find($schoolYear->id))->not->toBeNull();
    expect(SchoolYear::find($schoolYear->id)->trashed())->toBeFalse();
});

test('deleting a school year succeeds with the correct security code once one is set', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    app(AdminSecurityCodeService::class)->setCode($superAdmin, 'a-secret-code');
    $schoolYear = SchoolYear::factory()->create(['is_active' => false]);

    $response = $this->actingAs($superAdmin)->delete(route('school-years.destroy', $schoolYear), [
        'security_code' => 'a-secret-code',
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();
    expect(SchoolYear::withTrashed()->find($schoolYear->id)->trashed())->toBeTrue();
});
