<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }

            if (! Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'contract_started_at')) {
                $table->date('contract_started_at')->nullable()->after('created_by');
            }

            if (! Schema::hasColumn('users', 'password_must_change')) {
                $table->boolean('password_must_change')->default(false)->after('contract_started_at');
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropForeign(['created_by']);
            }

            $columns = array_filter([
                Schema::hasColumn('users', 'is_active') ? 'is_active' : null,
                Schema::hasColumn('users', 'created_by') ? 'created_by' : null,
                Schema::hasColumn('users', 'contract_started_at') ? 'contract_started_at' : null,
                Schema::hasColumn('users', 'password_must_change') ? 'password_must_change' : null,
                Schema::hasColumn('users', 'deleted_at') ? 'deleted_at' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
