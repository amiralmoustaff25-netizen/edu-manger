<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'specialite')) {
                $table->string('specialite')->nullable();
            }

            if (! Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone')->nullable();
            }

            if (! Schema::hasColumn('users', 'date_naissance')) {
                $table->date('date_naissance')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('users', 'specialite') ? 'specialite' : null,
                Schema::hasColumn('users', 'telephone') ? 'telephone' : null,
                Schema::hasColumn('users', 'date_naissance') ? 'date_naissance' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
