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
    Schema::create('classroom_matiere', function (Blueprint $table) {
        $table->id();
        
        // Liens vers la classe et la matière
        $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
        $table->foreignId('matiere_id')->constrained('matieres')->onDelete('cascade');
        
        // Le coefficient flexible pour cette classe précise
        $table->integer('coefficient')->default(1); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_matiere');
    }
};
