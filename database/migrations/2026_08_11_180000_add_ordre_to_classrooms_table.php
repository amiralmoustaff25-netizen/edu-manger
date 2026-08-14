<?php

use App\Support\ClassroomLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedTinyInteger('ordre')->nullable()->after('cycle');
        });

        ClassroomLevel::backfillOrdre();
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn('ordre');
        });
    }
};
