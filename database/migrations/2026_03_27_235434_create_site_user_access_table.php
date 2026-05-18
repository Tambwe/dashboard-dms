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
        Schema::create('site_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->boolean('can_edit')->default(true)->comment('Peut modifier les données du site');
            $table->boolean('can_collect')->default(true)->comment('Peut collecter des données');
            $table->timestamp('granted_at')->useCurrent()->comment('Date d\'attribution de l\'accès');
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null')->comment('Utilisateur admin qui a donné l\'accès');
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->unique(['user_id', 'site_id']);
            $table->index('user_id');
            $table->index('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_user_access');
    }
};
