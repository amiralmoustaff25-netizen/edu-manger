<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * teachers.rib est chiffré (Teacher::setRibAttribute(), AES-256 + enveloppe JSON
     * base64) avant stockage — le résultat dépasse régulièrement les 255 caractères d'un
     * VARCHAR standard selon la longueur du RIB en clair, provoquant "Data too long for
     * column 'rib'" sur MySQL (jamais détecté en local : SQLite n'impose aucune limite de
     * longueur sur une colonne texte). TEXT plutôt qu'un VARCHAR plus large : la longueur
     * du chiffré n'est pas bornée de façon prévisible.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->text('rib')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('rib')->nullable()->change();
        });
    }
};
