<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sites')) {
            return;
        }

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code_site')->nullable()->unique();
            $table->foreignId('type_site_id')->nullable()->constrained('type_sites')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->constrained('communes')->nullOnDelete();
            $table->foreignId('gestionnaire_id')->nullable()->constrained('gestionnaires')->nullOnDelete();
            $table->foreignId('coordinateur_id')->nullable()->constrained('coordinateurs')->nullOnDelete();
            $table->string('province')->nullable();
            $table->string('code_province')->nullable();
            $table->string('territoire')->nullable();
            $table->string('code_territoire')->nullable();
            $table->string('zone_sante')->nullable();
            $table->string('code_zone_sante')->nullable();
            $table->string('aire_sante')->nullable();
            $table->string('code_aire_sante')->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->integer('menages')->default(0);
            $table->integer('individus')->default(0);
            $table->integer('f_0_5')->default(0);
            $table->integer('f_6_17')->default(0);
            $table->integer('f_18_59')->default(0);
            $table->integer('f_60_plus')->default(0);
            $table->integer('h_0_5')->default(0);
            $table->integer('h_6_17')->default(0);
            $table->integer('h_18_59')->default(0);
            $table->integer('h_60_plus')->default(0);
            $table->string('source')->nullable();
            $table->string('round')->nullable();
            $table->string('type_gestion')->nullable();
            $table->date('date_mise_a_jour')->nullable();
            $table->string('type_fichier')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};