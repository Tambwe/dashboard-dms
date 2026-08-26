<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_question_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('questionnaire_id')->constrained('mobile_questionnaires')->cascadeOnDelete();
            $table->json('visible_question_keys');
            $table->timestamps();

            $table->unique(['user_id', 'questionnaire_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_question_preferences');
    }
};
