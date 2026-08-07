<?php

use App\Support\ProgramStatus;

it('it_allows_valid_transitions', function () {
    expect(ProgramStatus::canTransition('brouillon', 'soumis'))->toBeTrue();
    // Validation en une seule étape par un administrateur.
    expect(ProgramStatus::canTransition('soumis', 'valide_directeur'))->toBeTrue();
    // L'ancien état intermédiaire reste transitionnable pour les programmes existants.
    expect(ProgramStatus::canTransition('valide_surveillant', 'valide_directeur'))->toBeTrue();
});

it('it_blocks_invalid_transitions', function () {
    expect(ProgramStatus::canTransition('brouillon', 'valide_directeur'))->toBeFalse();
    expect(ProgramStatus::canTransition('valide_directeur', 'soumis'))->toBeFalse();
});

it('it_handles_reject_transitions', function () {
    expect(ProgramStatus::canTransition('soumis', 'rejete'))->toBeTrue();
    expect(ProgramStatus::canTransition('valide_surveillant', 'rejete'))->toBeTrue();
});
