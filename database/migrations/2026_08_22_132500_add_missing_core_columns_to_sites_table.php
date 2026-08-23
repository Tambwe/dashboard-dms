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
        if (! Schema::hasTable('sites')) {
            return;
        }

        Schema::table('sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sites', 'type_site_id')) {
                $table->unsignedBigInteger('type_site_id')->nullable();
            }
            if (! Schema::hasColumn('sites', 'commune_id')) {
                $table->unsignedBigInteger('commune_id')->nullable();
            }
            if (! Schema::hasColumn('sites', 'gestionnaire_id')) {
                $table->unsignedBigInteger('gestionnaire_id')->nullable();
            }
            if (! Schema::hasColumn('sites', 'coordinateur_id')) {
                $table->unsignedBigInteger('coordinateur_id')->nullable();
            }
            if (! Schema::hasColumn('sites', 'province')) {
                $table->string('province')->nullable();
            }
            if (! Schema::hasColumn('sites', 'code_province')) {
                $table->string('code_province')->nullable();
            }
            if (! Schema::hasColumn('sites', 'territoire')) {
                $table->string('territoire')->nullable();
            }
            if (! Schema::hasColumn('sites', 'code_territoire')) {
                $table->string('code_territoire')->nullable();
            }
            if (! Schema::hasColumn('sites', 'zone_sante')) {
                $table->string('zone_sante')->nullable();
            }
            if (! Schema::hasColumn('sites', 'code_zone_sante')) {
                $table->string('code_zone_sante')->nullable();
            }
            if (! Schema::hasColumn('sites', 'aire_sante')) {
                $table->string('aire_sante')->nullable();
            }
            if (! Schema::hasColumn('sites', 'code_aire_sante')) {
                $table->string('code_aire_sante')->nullable();
            }
            if (! Schema::hasColumn('sites', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('sites', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('sites', 'menages')) {
                $table->integer('menages')->nullable();
            }
            if (! Schema::hasColumn('sites', 'individus')) {
                $table->integer('individus')->nullable();
            }
            if (! Schema::hasColumn('sites', 'f_0_5')) {
                $table->integer('f_0_5')->nullable();
            }
            if (! Schema::hasColumn('sites', 'f_6_17')) {
                $table->integer('f_6_17')->nullable();
            }
            if (! Schema::hasColumn('sites', 'f_18_59')) {
                $table->integer('f_18_59')->nullable();
            }
            if (! Schema::hasColumn('sites', 'f_60_plus')) {
                $table->integer('f_60_plus')->nullable();
            }
            if (! Schema::hasColumn('sites', 'h_0_5')) {
                $table->integer('h_0_5')->nullable();
            }
            if (! Schema::hasColumn('sites', 'h_6_17')) {
                $table->integer('h_6_17')->nullable();
            }
            if (! Schema::hasColumn('sites', 'h_18_59')) {
                $table->integer('h_18_59')->nullable();
            }
            if (! Schema::hasColumn('sites', 'h_60_plus')) {
                $table->integer('h_60_plus')->nullable();
            }
            if (! Schema::hasColumn('sites', 'source')) {
                $table->string('source')->nullable();
            }
            if (! Schema::hasColumn('sites', 'round')) {
                $table->string('round')->nullable();
            }
            if (! Schema::hasColumn('sites', 'type_gestion')) {
                $table->string('type_gestion')->nullable();
            }
            if (! Schema::hasColumn('sites', 'date_mise_a_jour')) {
                $table->date('date_mise_a_jour')->nullable();
            }
            if (! Schema::hasColumn('sites', 'type_fichier')) {
                $table->string('type_fichier')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migration de rattrapage: rollback non destructif volontaire.
    }
};
