<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categorie_mouvements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nom de la catégorie: nouvelle entree, sortie');
            $table->string('code')->nullable()->comment('Code court de la catégorie');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insérer les catégories par défaut
        DB::table('categorie_mouvements')->insert([
            [
                'name' => 'nouvelle entree',
                'code' => 'ENTREE',
                'description' => 'Nouvelles personnes arrivant sur le site',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'sortie',
                'code' => 'SORTIE',
                'description' => 'Personnes quittant le site (retour)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorie_mouvements');
    }
};
