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
        Schema::table('classroom_fees', function (Blueprint $table) {
            $table->dropUnique(['classroom_id', 'fee_type_id', 'school_year_id']);
        });

        Schema::table('classroom_fees', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('amount');
            $table->boolean('is_current')->default(true)->after('version');
            $table->foreignId('previous_id')->nullable()->after('is_current')
                ->references('id')->on('classroom_fees')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->after('previous_id')
                ->references('id')->on('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->after('created_by')
                ->references('id')->on('users')->onDelete('set null');
            $table->softDeletes();
            $table->index(['classroom_id', 'fee_type_id', 'school_year_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_fees', function (Blueprint $table) {
            $table->dropIndex(['classroom_id', 'fee_type_id', 'school_year_id', 'is_current']);
            $table->dropForeign(['previous_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['version', 'is_current', 'previous_id', 'created_by', 'deleted_by', 'deleted_at']);
        });

        Schema::table('classroom_fees', function (Blueprint $table) {
            $table->unique(['classroom_id', 'fee_type_id', 'school_year_id']);
        });
    }
};
