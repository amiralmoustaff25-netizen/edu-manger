<?php

use App\Support\SchoolYearStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            $table->timestamp('closing_started_at')->nullable()->after('status');
            $table->timestamp('closed_at')->nullable()->after('closing_started_at');
            $table->timestamp('archived_at')->nullable()->after('closed_at');
            $table->timestamp('reopened_at')->nullable()->after('archived_at');
            $table->foreignId('reopened_by')->nullable()->after('reopened_at')
                ->constrained('users')->nullOnDelete();
        });

        SchoolYearStatus::migrateLegacyValues();
    }

    public function down(): void
    {
        $reversedMapping = array_flip(SchoolYearStatus::LEGACY_MAPPING);
        foreach ($reversedMapping as $current => $legacy) {
            DB::table('school_years')->where('status', $current)->update(['status' => $legacy]);
        }
        // 'closing' et 'archived' n'ont pas d'équivalent dans l'ancien vocabulaire : les
        // années dans ces états retombent sur 'completed', le plus proche sémantiquement.
        DB::table('school_years')->whereIn('status', [SchoolYearStatus::CLOSING, SchoolYearStatus::ARCHIVED])
            ->update(['status' => 'completed']);

        Schema::table('school_years', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn(['closing_started_at', 'closed_at', 'archived_at', 'reopened_at']);
        });
    }
};
