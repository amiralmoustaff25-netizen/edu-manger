<?php

use App\Models\Classroom;
use App\Models\ClassroomFee;
use App\Models\FeeType;
use App\Models\SchoolYear;
use App\Models\User;

function makeTariffFixtures(): array
{
    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $feeType = FeeType::create([
        'name' => 'Scolarité mensuelle',
        'code' => 'mensualite',
        'is_recurring' => true,
        'is_optional' => false,
    ]);

    return compact('schoolYear', 'classroom', 'feeType');
}

test('a plain comptable cannot create a classroom tariff', function () {
    $comptable = User::factory()->create();
    $comptable->assignRole('comptable');

    $fixtures = makeTariffFixtures();

    $response = $this->actingAs($comptable)->post('/classroom-fees', [
        'classroom_id' => $fixtures['classroom']->id,
        'fee_type_id' => $fixtures['feeType']->id,
        'school_year_id' => $fixtures['schoolYear']->id,
        'amount' => 15000,
    ]);

    $response->assertForbidden();
    expect(ClassroomFee::count())->toBe(0);
});

test('a manager comptable can create a classroom tariff', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $fixtures = makeTariffFixtures();

    $response = $this->actingAs($manager)->post('/classroom-fees', [
        'classroom_id' => $fixtures['classroom']->id,
        'fee_type_id' => $fixtures['feeType']->id,
        'school_year_id' => $fixtures['schoolYear']->id,
        'amount' => 15000,
    ]);

    $response->assertRedirect(route('classroom-fees.index'));

    $tariff = ClassroomFee::first();
    expect($tariff->amount)->toEqual(15000);
    expect($tariff->version)->toBe(1);
    expect($tariff->is_current)->toBeTrue();
    expect($tariff->created_by)->toBe($manager->id);
});

test('updating a tariff creates a new version and archives the previous one', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $fixtures = makeTariffFixtures();

    $original = ClassroomFee::create([
        'classroom_id' => $fixtures['classroom']->id,
        'fee_type_id' => $fixtures['feeType']->id,
        'school_year_id' => $fixtures['schoolYear']->id,
        'amount' => 15000,
        'version' => 1,
        'is_current' => true,
        'created_by' => $manager->id,
    ]);

    $response = $this->actingAs($manager)->put("/classroom-fees/{$original->id}", [
        'classroom_id' => $fixtures['classroom']->id,
        'fee_type_id' => $fixtures['feeType']->id,
        'school_year_id' => $fixtures['schoolYear']->id,
        'amount' => 18000,
    ]);

    $response->assertRedirect(route('classroom-fees.index'));

    expect($original->refresh()->is_current)->toBeFalse();
    expect(ClassroomFee::current()->count())->toBe(1);

    $newVersion = ClassroomFee::current()->first();
    expect($newVersion->amount)->toEqual(18000);
    expect($newVersion->version)->toBe(2);
    expect($newVersion->previous_id)->toBe($original->id);
});

test('deleting a tariff soft-deletes it and records who deleted it', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $fixtures = makeTariffFixtures();

    $tariff = ClassroomFee::create([
        'classroom_id' => $fixtures['classroom']->id,
        'fee_type_id' => $fixtures['feeType']->id,
        'school_year_id' => $fixtures['schoolYear']->id,
        'amount' => 15000,
        'version' => 1,
        'is_current' => true,
        'created_by' => $manager->id,
    ]);

    $response = $this->actingAs($manager)->delete("/classroom-fees/{$tariff->id}");

    $response->assertRedirect(route('classroom-fees.index'));

    expect(ClassroomFee::withTrashed()->find($tariff->id)->deleted_by)->toBe($manager->id);
    expect(ClassroomFee::find($tariff->id))->toBeNull();
});
