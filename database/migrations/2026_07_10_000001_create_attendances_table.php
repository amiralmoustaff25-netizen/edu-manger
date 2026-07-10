<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'classroom_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
