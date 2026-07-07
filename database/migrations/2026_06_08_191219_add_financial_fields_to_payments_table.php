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
        Schema::table('payments', function (Blueprint $table) {
            // On n'ajoute plus 'status' puisqu'il existe déjà dans ta table d'origine
            $table->string('month')->after('remaining_balance');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null')->after('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['month', 'validated_by']);
        });
    }
};
