<?php

use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates ancienneté in years and months', function () {
    // subMonthsNoOverflow (pas subMonths) : sur un jour qui n'existe pas 4 mois plus tôt
    // (ex: 31 août -> 31 avril inexistant), subMonths() déborde sur le mois suivant et
    // décale le calcul d'un mois selon le jour du mois où le test tourne — flaky constaté
    // le 31/08. NoOverflow ancre plutôt sur le dernier jour valide du mois cible.
    $teacher = Teacher::factory()->create([
        'date_recrutement' => now()->subYears(3)->subMonthsNoOverflow(4)->format('Y-m-d'),
    ]);

    expect($teacher->anciennete())->toContain('3 ans')->toContain('4 mois');
});
