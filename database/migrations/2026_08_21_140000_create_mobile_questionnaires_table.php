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
        Schema::create('mobile_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(false);
            $table->json('survey');
            $table->json('choices')->nullable();
            $table->json('settings')->nullable();
            $table->string('source_file')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['code', 'version']);
            $table->index(['code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_questionnaires');
    }
};
