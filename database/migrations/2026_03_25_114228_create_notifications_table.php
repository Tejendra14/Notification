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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->string('user_id')->index();
            $table->string('type'); // email, sms, push
            $table->string('channel'); // notification, marketing, alert
            $table->json('payload');
            $table->string('status')->default('pending'); // pending, processing, sent, failed
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
