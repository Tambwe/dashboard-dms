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
        Schema::create('ossat_choices', function (Blueprint $table) {
            $table->id();
            $table->string('groupe', 60)->index();          // ex: type_installation, comites, sources_eau
            $table->string('valeur', 120);                  // valeur stockée en BDD / dans les rapports
            $table->string('libelle', 180)->nullable();     // libellé affiché (null = même que valeur)
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['groupe', 'valeur']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ossat_choices');
    }
};
