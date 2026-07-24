<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('information'); // information, important, urgent, reminder, announcement
            $table->string('priority')->default('normal'); // normal, important, urgent
            $table->string('target_mode')->default('all'); // all, roles, classroom, users
            $table->json('target_roles')->nullable();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->json('target_user_ids')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('status')->default('draft'); // draft, scheduled, published, expired, archived
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['target_mode', 'classroom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
