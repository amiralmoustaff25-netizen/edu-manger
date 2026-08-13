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
        // SEC-spec "Double authentification Super Admin" (TOTP, compatible Google
        // Authenticator/Authy). secret et recovery_codes sont chiffrés au niveau
        // applicatif (cast 'encrypted'/'encrypted:array' sur le modèle User) — pas
        // en clair même en cas de fuite de la base. confirmed_at distingue un
        // secret généré mais jamais validé (abandon en cours d'activation) d'une
        // activation réellement effective.
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('security_code_updated_at');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
