<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapter_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_chapter_id')->constrained()->cascadeOnDelete();
            $table->date('date_traitement');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarque')->nullable();
            $table->timestamps();

            $table->unique(['program_chapter_id', 'date_traitement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_completions');
    }
};
