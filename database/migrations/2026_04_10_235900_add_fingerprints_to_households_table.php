<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table) {
            // Empreinte 2 et 3 (la 1ère est chef_empreinte existante)
            $table->text('chef_empreinte_2')->nullable()->after('chef_empreinte');
            $table->text('chef_empreinte_3')->nullable()->after('chef_empreinte_2');

            // Hash SHA-256 des templates FMR_ISO pour détection rapide de doublons
            // (255 chars = longueur d'un SHA-256 hex)
            $table->string('chef_empreinte_hash_1', 64)->nullable()->after('chef_empreinte_3');
            $table->string('chef_empreinte_hash_2', 64)->nullable()->after('chef_empreinte_hash_1');
            $table->string('chef_empreinte_hash_3', 64)->nullable()->after('chef_empreinte_hash_2');

            // Index sur les hashes pour recherche rapide de doublons
            $table->index('chef_empreinte_hash_1', 'idx_fp_hash_1');
            $table->index('chef_empreinte_hash_2', 'idx_fp_hash_2');
            $table->index('chef_empreinte_hash_3', 'idx_fp_hash_3');
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropIndex('idx_fp_hash_1');
            $table->dropIndex('idx_fp_hash_2');
            $table->dropIndex('idx_fp_hash_3');

            $table->dropColumn([
                'chef_empreinte_2',
                'chef_empreinte_3',
                'chef_empreinte_hash_1',
                'chef_empreinte_hash_2',
                'chef_empreinte_hash_3',
            ]);
        });
    }
};
