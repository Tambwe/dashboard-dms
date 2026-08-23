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
        if (!Schema::hasTable('mobile_questionnaires')) {
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

        if (!Schema::hasTable('mobile_questionnaire_submissions')) {
            Schema::create('mobile_questionnaire_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('questionnaire_id')->constrained('mobile_questionnaires')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
                $table->foreignId('territoire_id')->nullable()->constrained('territoires')->nullOnDelete();
                $table->foreignId('commune_id')->nullable()->constrained('communes')->nullOnDelete();
                $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
                $table->date('date_collecte')->nullable();
                $table->json('answers');
                $table->string('status')->default('submitted');
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->index(['questionnaire_id', 'status']);
                $table->index(['site_id', 'date_collecte']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_questionnaire_submissions');
        Schema::dropIfExists('mobile_questionnaires');
    }
};
