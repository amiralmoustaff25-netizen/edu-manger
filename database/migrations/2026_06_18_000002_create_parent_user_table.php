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
        Schema::create('parent_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('lien_parente', ['Pere', 'Mere', 'Tuteur', 'Tutrice', 'Autre'])->default('Autre');
            $table->boolean('est_responsable_financier')->default(false);
            $table->boolean('est_contact_urgence')->default(false);
            $table->timestamps();

            // Un parent peut être lié plusieurs fois au même élève avec des liens différents
            $table->unique(['parent_id', 'user_id', 'lien_parente']);

            // Index pour optimiser les recherches
            $table->index('parent_id');
            $table->index('user_id');
            $table->index('est_responsable_financier');
            $table->index('est_contact_urgence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_user');
    }
};
