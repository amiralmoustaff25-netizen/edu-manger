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
        // Indexes pour la table users
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasIndex('users', 'users_role_index')) {
                $table->index('role');
            }
            if (! Schema::hasIndex('users', 'users_cycle_index')) {
                $table->index('cycle');
            }
            if (! Schema::hasIndex('users', 'users_matricule_index')) {
                $table->index('matricule');
            }
            // email index already exists (unique)
        });

        // Indexes pour la table registrations
        Schema::table('registrations', function (Blueprint $table) {
            if (! Schema::hasIndex('registrations', 'registrations_user_id_index')) {
                $table->index('user_id');
            }
            if (! Schema::hasIndex('registrations', 'registrations_classroom_id_index')) {
                $table->index('classroom_id');
            }
            if (! Schema::hasIndex('registrations', 'registrations_school_year_id_index')) {
                $table->index('school_year_id');
            }
            if (! Schema::hasIndex('registrations', 'registrations_status_index')) {
                $table->index('status');
            }
            if (! Schema::hasIndex('registrations', 'registrations_academic_year_index')) {
                $table->index('academic_year');
            }
            if (! Schema::hasIndex('registrations', 'registrations_matricule_index')) {
                $table->index('matricule');
            }
        });

        // Indexes pour la table classrooms
        Schema::table('classrooms', function (Blueprint $table) {
            if (! Schema::hasIndex('classrooms', 'classrooms_teacher_id_index')) {
                $table->index('teacher_id');
            }
            if (! Schema::hasIndex('classrooms', 'classrooms_school_year_id_index')) {
                $table->index('school_year_id');
            }
            if (! Schema::hasIndex('classrooms', 'classrooms_name_index')) {
                $table->index('name');
            }
        });

        // Indexes pour la table payments
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasIndex('payments', 'payments_registration_id_index')) {
                $table->index('registration_id');
            }
            if (! Schema::hasIndex('payments', 'payments_status_index')) {
                $table->index('status');
            }
            if (! Schema::hasIndex('payments', 'payments_payment_date_index')) {
                $table->index('payment_date');
            }
            if (! Schema::hasIndex('payments', 'payments_month_index')) {
                $table->index('month');
            }
        });

        // Indexes pour la table notes
        Schema::table('notes', function (Blueprint $table) {
            if (! Schema::hasIndex('notes', 'notes_user_id_index')) {
                $table->index('user_id');
            }
            if (! Schema::hasIndex('notes', 'notes_classroom_id_index')) {
                $table->index('classroom_id');
            }
            if (! Schema::hasIndex('notes', 'notes_matiere_id_index')) {
                $table->index('matiere_id');
            }
            if (! Schema::hasIndex('notes', 'notes_periode_index')) {
                $table->index('periode');
            }
            if (! Schema::hasIndex('notes', 'notes_type_evaluation_index')) {
                $table->index('type_evaluation');
            }
        });

        // Indexes pour la table attendances
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasIndex('attendances', 'attendances_user_id_index')) {
                $table->index('user_id');
            }
            if (! Schema::hasIndex('attendances', 'attendances_classroom_id_index')) {
                $table->index('classroom_id');
            }
            if (! Schema::hasIndex('attendances', 'attendances_date_index')) {
                $table->index('date');
            }
            if (! Schema::hasIndex('attendances', 'attendances_status_index')) {
                $table->index('status');
            }
        });

        // Indexes pour la table parents
        Schema::table('parents', function (Blueprint $table) {
            if (! Schema::hasIndex('parents', 'parents_user_id_index')) {
                $table->index('user_id');
            }
            if (! Schema::hasIndex('parents', 'parents_matricule_parent_index')) {
                $table->index('matricule_parent');
            }
            // statut index already exists
            if (! Schema::hasIndex('parents', 'parents_email_index')) {
                $table->index('email');
            }
        });

        // Indexes pour la table school_years
        Schema::table('school_years', function (Blueprint $table) {
            if (! Schema::hasIndex('school_years', 'school_years_is_active_index')) {
                $table->index('is_active');
            }
            if (! Schema::hasIndex('school_years', 'school_years_year_string_index')) {
                $table->index('year_string');
            }
        });

        // Indexes pour la table teacher_classroom (pivot)
        Schema::table('teacher_classroom', function (Blueprint $table) {
            if (! Schema::hasIndex('teacher_classroom', 'teacher_classroom_teacher_id_index')) {
                $table->index('teacher_id');
            }
            if (! Schema::hasIndex('teacher_classroom', 'teacher_classroom_classroom_id_index')) {
                $table->index('classroom_id');
            }
            if (! Schema::hasIndex('teacher_classroom', 'teacher_classroom_matiere_id_index')) {
                $table->index('matiere_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes pour la table users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['cycle']);
            $table->dropIndex(['matricule']);
            $table->dropIndex(['email']);
        });

        // Drop indexes pour la table registrations
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['classroom_id']);
            $table->dropIndex(['school_year_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['academic_year']);
            $table->dropIndex(['matricule']);
        });

        // Drop indexes pour la table classrooms
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropIndex(['teacher_id']);
            $table->dropIndex(['school_year_id']);
            $table->dropIndex(['name']);
        });

        // Drop indexes pour la table payments
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['month']);
        });

        // Drop indexes pour la table notes
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['classroom_id']);
            $table->dropIndex(['matiere_id']);
            $table->dropIndex(['periode']);
            $table->dropIndex(['type_evaluation']);
        });

        // Drop indexes pour la table attendances
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['classroom_id']);
            $table->dropIndex(['date']);
            $table->dropIndex(['status']);
        });

        // Drop indexes pour la table parents
        Schema::table('parents', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['matricule_parent']);
            $table->dropIndex(['statut']);
            $table->dropIndex(['email']);
        });

        // Drop indexes pour la table school_years
        Schema::table('school_years', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['year_string']);
        });

        // Drop indexes pour la table teacher_classroom (pivot)
        Schema::table('teacher_classroom', function (Blueprint $table) {
            $table->dropIndex(['teacher_id']);
            $table->dropIndex(['classroom_id']);
            $table->dropIndex(['matiere_id']);
        });
    }
};
