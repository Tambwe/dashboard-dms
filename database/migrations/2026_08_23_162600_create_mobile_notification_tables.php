<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('device_uuid')->unique();
            $table->string('expo_push_token')->nullable()->unique();
            $table->string('device_name')->nullable();
            $table->string('platform', 20);
            $table->string('app_version', 50)->nullable();
            $table->boolean('notifications_enabled')->default(false);
            $table->timestamp('last_login_at');
            $table->timestamp('last_notification_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'notifications_enabled']);
            $table->index('last_login_at');
        });

        Schema::create('mobile_push_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 100);
            $table->text('body');
            $table->json('data')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_push_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_push_notification_id');
            $table->foreignId('mobile_device_id');
            $table->string('token_snapshot');
            $table->string('status', 20)->default('pending');
            $table->string('ticket_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['mobile_push_notification_id', 'mobile_device_id'],
                'mobile_push_delivery_unique'
            );
            $table
                ->foreign('mobile_push_notification_id', 'mobile_push_delivery_notification_fk')
                ->references('id')
                ->on('mobile_push_notifications')
                ->cascadeOnDelete();
            $table
                ->foreign('mobile_device_id', 'mobile_push_delivery_device_fk')
                ->references('id')
                ->on('mobile_devices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_push_notification_deliveries');
        Schema::dropIfExists('mobile_push_notifications');
        Schema::dropIfExists('mobile_devices');
    }
};
