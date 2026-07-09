<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'prenom')) {
                $table->string('prenom')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'date_naissance')) {
                $table->date('date_naissance')->nullable()->after('prenom');
            }

            if (! Schema::hasColumn('users', 'lieu_naissance')) {
                $table->string('lieu_naissance')->nullable()->after('date_naissance');
            }

            if (! Schema::hasColumn('users', 'sexe')) {
                $table->enum('sexe', ['M', 'F'])->nullable()->after('lieu_naissance');
            }

            if (! Schema::hasColumn('users', 'nationalite')) {
                $table->string('nationalite')->nullable()->after('sexe');
            }

            if (! Schema::hasColumn('users', 'adresse')) {
                $table->text('adresse')->nullable()->after('nationalite');
            }

            if (! Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone')->nullable()->after('adresse');
            }

            if (! Schema::hasColumn('users', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('telephone');
            }

            if (! Schema::hasColumn('users', 'etablissement_precedent')) {
                $table->string('etablissement_precedent')->nullable()->after('photo_path');
            }

            if (! Schema::hasColumn('users', 'dernier_resultat')) {
                $table->string('dernier_resultat')->nullable()->after('etablissement_precedent');
            }

            if (! Schema::hasColumn('users', 'pere_nom')) {
                $table->string('pere_nom')->nullable()->after('dernier_resultat');
            }

            if (! Schema::hasColumn('users', 'pere_profession')) {
                $table->string('pere_profession')->nullable()->after('pere_nom');
            }

            if (! Schema::hasColumn('users', 'pere_telephone')) {
                $table->string('pere_telephone')->nullable()->after('pere_profession');
            }

            if (! Schema::hasColumn('users', 'mere_nom')) {
                $table->string('mere_nom')->nullable()->after('pere_telephone');
            }

            if (! Schema::hasColumn('users', 'mere_profession')) {
                $table->string('mere_profession')->nullable()->after('mere_nom');
            }

            if (! Schema::hasColumn('users', 'mere_telephone')) {
                $table->string('mere_telephone')->nullable()->after('mere_profession');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'prenom', 'date_naissance', 'lieu_naissance', 'sexe', 'nationalite',
                'adresse', 'telephone', 'photo_path',
                'etablissement_precedent', 'dernier_resultat',
                'pere_nom', 'pere_profession', 'pere_telephone',
                'mere_nom', 'mere_profession', 'mere_telephone',
            ]);
        });
    }
};
