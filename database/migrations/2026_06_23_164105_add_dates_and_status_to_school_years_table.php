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
        Schema::table('school_years', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('year_string');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('status')->default('upcoming')->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['start_date', 'end_date', 'status']);
        });
    }
};
