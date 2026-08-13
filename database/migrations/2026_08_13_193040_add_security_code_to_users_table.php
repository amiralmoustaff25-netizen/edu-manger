<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SEC-spec "Code de sécurité Administrateur" : indépendant du mot de passe
        // de connexion, requis pour les actions critiques (suppression définitive,
        // suppression d'année scolaire...). Hashé comme un mot de passe, jamais
        // stocké/affiché en clair. Nullable : tant qu'un super-admin ne l'a pas
        // défini, les actions concernées restent accessibles sans blocage (voir
        // AdminSecurityCodeService/VerifySecurityCode) — pas de rupture pour les
        // comptes existants au déploiement de cette fonctionnalité.
        Schema::table('users', function (Blueprint $table) {
            $table->string('security_code')->nullable()->after('password');
            $table->timestamp('security_code_updated_at')->nullable()->after('security_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['security_code', 'security_code_updated_at']);
        });
    }
};
