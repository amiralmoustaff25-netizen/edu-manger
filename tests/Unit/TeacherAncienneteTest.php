<?php

use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates ancienneté in years and months', function () {
    $teacher = Teacher::factory()->create([
        'date_recrutement' => now()->subYears(3)->subMonths(4)->format('Y-m-d'),
    ]);

    expect($teacher->anciennete())->toContain('3 ans')->toContain('4 mois');
});
