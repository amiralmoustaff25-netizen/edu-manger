<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Durcit au niveau base plusieurs contraintes ON DELETE CASCADE
     * identifiées par l'audit comme risque de perte de données en cascade
     * lors d'une suppression physique future (forceDelete / script de
     * maintenance) :
     *   - ARC-02 : classroom_fees.fee_type_id -> effacerait toute la grille
     *     tarifaire liée à un type de frais supprimé.
     *   - BDD-02 : payments.registration_id -> contredit la règle métier
     *     documentée ("un paiement n'est jamais supprimé, seulement annulé").
     *   - BDD-03 : notes/attendances/sanctions/student_documents.user_id ->
     *     effacerait l'historique pédagogique/disciplinaire d'un élève.
     *   - BDD-15 : classrooms.school_year_id -> effacerait toutes les
     *     classes d'une année scolaire supprimée (incohérent avec
     *     registrations.school_year_id, déjà en restrict).
     *
     * On ne change PAS le comportement des suppressions logiques
     * (SoftDeletes), qui continuent de fonctionner normalement : seule une
     * suppression physique (DELETE réel) est désormais bloquée par la
     * base si des lignes dépendantes existent encore.
     */
    public function up(): void
    {
        Schema::table('classroom_fees', function (Blueprint $table) {
            $table->dropForeign(['fee_type_id']);
            $table->foreign('fee_type_id')->references('id')->on('fee_types')->onDelete('restrict');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['registration_id']);
            $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('restrict');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::table('sanctions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::table('student_documents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['school_year_id']);
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('classroom_fees', function (Blueprint $table) {
            $table->dropForeign(['fee_type_id']);
            $table->foreign('fee_type_id')->references('id')->on('fee_types')->onDelete('cascade');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['registration_id']);
            $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('cascade');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('sanctions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('student_documents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['school_year_id']);
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
        });
    }
};
