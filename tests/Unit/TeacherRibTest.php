<?php

use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('stores the RIB encrypted and can decrypt it back, unlike the previous bcrypt hash', function () {
    // Régression Phase 2 (finding M7) : bcrypt() est un hachage à sens unique — le RIB
    // n'était donc jamais récupérable, y compris pour l'usage métier légitime (virement de
    // salaire) auquel il est destiné.
    $teacher = Teacher::factory()->create(['rib' => 'FR761234598765']);

    $rawStored = DB::table('teachers')->where('id', $teacher->id)->value('rib');
    expect($rawStored)->not->toBe('FR761234598765');
    expect(Crypt::decryptString($rawStored))->toBe('FR761234598765');

    $teacher->refresh();
    expect($teacher->rib)->toBe('FR761234598765');
});

it('does not crash when reading a pre-existing bcrypt-hashed rib', function () {
    $teacher = Teacher::factory()->create();
    DB::table('teachers')->where('id', $teacher->id)->update(['rib' => bcrypt('FR761234598765')]);

    $teacher->refresh();

    expect($teacher->rib)->not->toBeNull();
});
