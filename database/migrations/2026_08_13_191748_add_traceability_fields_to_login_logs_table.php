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
        // SEC-spec "Journal des connexions" : matricule/rôle sont capturés au moment
        // de la connexion (snapshot), pas recalculés depuis la relation user — un
        // changement de rôle ultérieur ne doit pas réécrire l'historique de sécurité.
        Schema::table('login_logs', function (Blueprint $table) {
            $table->string('matricule')->nullable()->after('email');
            $table->string('role')->nullable()->after('matricule');
            $table->string('browser')->nullable()->after('user_agent');
            $table->string('platform')->nullable()->after('browser');
            $table->string('device_type')->nullable()->after('platform');
            $table->timestamp('logout_at')->nullable()->after('login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->dropColumn(['matricule', 'role', 'browser', 'platform', 'device_type', 'logout_at']);
        });
    }
};
