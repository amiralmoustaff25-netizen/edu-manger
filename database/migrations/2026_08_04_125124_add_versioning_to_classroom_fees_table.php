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
        // Les deux opérations sur l'index composite doivent être dans le même ALTER
        // TABLE (donc le même Schema::table()) : sous MySQL, dropUnique() seul échoue
        // avec "Cannot drop index ... needed in a foreign key constraint" tant qu'aucun
        // autre index ne couvre encore classroom_id (leftmost de cet unique, seule
        // colonne indexée servant la FK) — jamais visible sous SQLite, qui n'impose
        // pas cette contrainte. Ajouter le nouvel index avant de supprimer l'ancien,
        // dans la même instruction, donne à MySQL une couverture continue.
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
            // Nom explicite : le nom auto-généré (73 caractères) dépasse la limite
            // d'identifiant MySQL (64) — jamais visible sous SQLite, qui n'a pas cette
            // limite.
            $table->index(['classroom_id', 'fee_type_id', 'school_year_id', 'is_current'], 'classroom_fees_current_lookup_index');
            $table->dropUnique(['classroom_id', 'fee_type_id', 'school_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Même raison qu'en up() : le nouvel index (celui qui remplace l'ancien) doit
        // être ajouté dans la même instruction que la suppression de
        // classroom_fees_current_lookup_index, sinon MySQL refuse cette suppression
        // (plus aucun index ne couvrirait alors classroom_id pour sa clé étrangère).
        Schema::table('classroom_fees', function (Blueprint $table) {
            $table->unique(['classroom_id', 'fee_type_id', 'school_year_id']);
            $table->dropIndex('classroom_fees_current_lookup_index');
            $table->dropForeign(['previous_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['version', 'is_current', 'previous_id', 'created_by', 'deleted_by', 'deleted_at']);
        });
    }
};
