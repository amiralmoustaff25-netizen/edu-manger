<?php

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function countQueriesFor(callable $callback): int
{
    DB::enableQueryLog();
    DB::flushQueryLog();
    $callback();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

test('parents index query count does not grow with the number of parents (no N+1 on students)', function () {
    // Warm-up request: absorbs one-time costs (permission cache load, etc.)
    // unrelated to row count, so the two real measurements below are comparable.
    $this->actingAs($this->admin)->get(route('parents.index'));

    ParentModel::factory()->create();
    $queriesForOne = countQueriesFor(fn () => $this->actingAs($this->admin)->get(route('parents.index')));

    ParentModel::factory()->count(9)->create();
    $queriesForTen = countQueriesFor(fn () => $this->actingAs($this->admin)->get(route('parents.index')));

    expect($queriesForTen)->toBe($queriesForOne);
});

test('classrooms index query count does not grow with the number of classrooms (no N+1 on teacher)', function () {
    $year = SchoolYear::create(['year_string' => '2026-2027', 'is_active' => true]);

    $this->actingAs($this->admin)->get(route('classrooms.index'));

    Classroom::factory()->create(['school_year_id' => $year->id]);
    $queriesForOne = countQueriesFor(fn () => $this->actingAs($this->admin)->get(route('classrooms.index')));

    Classroom::factory()->count(9)->create(['school_year_id' => $year->id]);
    $queriesForTen = countQueriesFor(fn () => $this->actingAs($this->admin)->get(route('classrooms.index')));

    expect($queriesForTen)->toBe($queriesForOne);
});
