<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_annual_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('program_chapters')->nullOnDelete();
            $table->integer('ordre')->default(1);
            $table->string('type');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->decimal('volume_horaire_prevu', 8, 2)->default(0);
            $table->decimal('volume_horaire_realise', 8, 2)->default(0);
            $table->timestamps();

            $table->index(['program_annual_id', 'parent_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_chapters');
    }
};
