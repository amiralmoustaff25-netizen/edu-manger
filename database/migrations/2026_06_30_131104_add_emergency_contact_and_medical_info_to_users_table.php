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
        Schema::table('users', function (Blueprint $table) {
            $table->string('emergency_contact_name')->nullable()->after('cycle');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->text('medical_notes')->nullable()->after('emergency_contact_phone');
            $table->text('allergies')->nullable()->after('medical_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['emergency_contact_name', 'emergency_contact_phone', 'medical_notes', 'allergies']);
        });
    }
};
