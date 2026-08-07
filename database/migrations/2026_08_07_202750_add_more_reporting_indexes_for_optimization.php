<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite (utilisé par cette application) n'indexe pas automatiquement les
 * colonnes de clé étrangère contrairement à MySQL/InnoDB. Les tables à fort
 * volume (journalisation, finance) ou interrogées via des relations hasMany
 * sur leur colonne étrangère restaient donc sans index depuis leur création.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            if (!Schema::hasIndex('login_logs', 'login_logs_user_id_index')) {
                $table->index('user_id');
            }
            if (!Schema::hasIndex('login_logs', 'login_logs_status_index')) {
                $table->index('status');
            }
            if (!Schema::hasIndex('login_logs', 'login_logs_login_at_index')) {
                $table->index('login_at');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasIndex('audit_logs', 'audit_logs_user_id_index')) {
                $table->index('user_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasIndex('invoices', 'invoices_registration_id_index')) {
                $table->index('registration_id');
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasIndex('invoice_items', 'invoice_items_invoice_id_index')) {
                $table->index('invoice_id');
            }
        });

        Schema::table('discounts', function (Blueprint $table) {
            if (!Schema::hasIndex('discounts', 'discounts_registration_id_index')) {
                $table->index('registration_id');
            }
        });

        Schema::table('credits', function (Blueprint $table) {
            if (!Schema::hasIndex('credits', 'credits_registration_id_index')) {
                $table->index('registration_id');
            }
            if (!Schema::hasIndex('credits', 'credits_payment_id_index')) {
                $table->index('payment_id');
            }
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            if (!Schema::hasIndex('credit_notes', 'credit_notes_registration_id_index')) {
                $table->index('registration_id');
            }
        });

        Schema::table('reminders', function (Blueprint $table) {
            if (!Schema::hasIndex('reminders', 'reminders_registration_id_index')) {
                $table->index('registration_id');
            }
            if (!Schema::hasIndex('reminders', 'reminders_payment_id_index')) {
                $table->index('payment_id');
            }
        });

        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasIndex('announcements', 'announcements_classroom_id_index')) {
                $table->index('classroom_id');
            }
        });

        Schema::table('program_chapters', function (Blueprint $table) {
            if (!Schema::hasIndex('program_chapters', 'program_chapters_program_annual_id_index')) {
                $table->index('program_annual_id');
            }
        });

        Schema::table('chapter_completions', function (Blueprint $table) {
            if (!Schema::hasIndex('chapter_completions', 'chapter_completions_program_chapter_id_index')) {
                $table->index('program_chapter_id');
            }
        });

        Schema::table('teaching_sessions', function (Blueprint $table) {
            if (!Schema::hasIndex('teaching_sessions', 'teaching_sessions_pedagogical_assignment_id_index')) {
                $table->index('pedagogical_assignment_id');
            }
        });

        Schema::table('student_documents', function (Blueprint $table) {
            if (!Schema::hasIndex('student_documents', 'student_documents_user_id_index')) {
                $table->index('user_id');
            }
        });

        Schema::table('user_permission_overrides', function (Blueprint $table) {
            if (!Schema::hasIndex('user_permission_overrides', 'user_permission_overrides_user_id_index')) {
                $table->index('user_id');
            }
        });

        Schema::table('user_role_history', function (Blueprint $table) {
            if (!Schema::hasIndex('user_role_history', 'user_role_history_user_id_index')) {
                $table->index('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['login_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
            $table->dropIndex(['payment_id']);
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
            $table->dropIndex(['payment_id']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['classroom_id']);
        });

        Schema::table('program_chapters', function (Blueprint $table) {
            $table->dropIndex(['program_annual_id']);
        });

        Schema::table('chapter_completions', function (Blueprint $table) {
            $table->dropIndex(['program_chapter_id']);
        });

        Schema::table('teaching_sessions', function (Blueprint $table) {
            $table->dropIndex(['pedagogical_assignment_id']);
        });

        Schema::table('student_documents', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('user_permission_overrides', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('user_role_history', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
