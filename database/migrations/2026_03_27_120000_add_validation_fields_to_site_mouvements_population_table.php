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
        Schema::table('site_mouvements_population', function (Blueprint $table) {
            // Statut de validation
            $table->enum('statut', ['en_attente', 'valide', 'rejete'])
                ->default('en_attente')
                ->after('created_by')
                ->comment('Statut de validation du mouvement');
            
            // Date de validation
            $table->timestamp('validated_at')
                ->nullable()
                ->after('statut')
                ->comment('Date de validation ou rejet');
            
            // Utilisateur qui a validé/rejeté
            $table->foreignId('validated_by')
                ->nullable()
                ->after('validated_at')
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Super admin ayant validé ou rejeté');
            
            // Raison du rejet
            $table->text('rejection_reason')
                ->nullable()
                ->after('validated_by')
                ->comment('Raison du rejet si applicable');
            
            // Index pour optimiser les requêtes par statut
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_mouvements_population', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'statut',
                'validated_at',
                'validated_by',
                'rejection_reason'
            ]);
        });
    }
};
