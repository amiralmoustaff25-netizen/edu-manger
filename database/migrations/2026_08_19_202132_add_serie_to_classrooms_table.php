<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            // Série du lycée (ex. L, S, ES) : uniquement pertinente pour cycle='lycee',
            // laissée vide pour le primaire/collège — voir ClassroomController::store()/update().
            $table->string('serie')->nullable()->after('cycle');
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn('serie');
        });
    }
};
