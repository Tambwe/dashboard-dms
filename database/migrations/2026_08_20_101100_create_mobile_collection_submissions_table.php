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
        if (Schema::hasTable('mobile_collection_submissions')) {
            return;
        }

        Schema::create('mobile_collection_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('sector');
            $table->string('sector')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('pending');
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_collection_submissions');
    }
};
