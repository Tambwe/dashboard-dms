<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sites')) {
            return;
        }

        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'organisation_id')) {
                $table->unsignedBigInteger('organisation_id')->nullable()->after('code_site');
            }
            if (!Schema::hasColumn('sites', 'type_site_id')) {
                $table->unsignedBigInteger('type_site_id')->nullable()->after('organisation_id');
            }
            if (!Schema::hasColumn('sites', 'commune_id')) {
                $table->unsignedBigInteger('commune_id')->nullable()->after('type_site_id');
            }
            if (!Schema::hasColumn('sites', 'gestionnaire_id')) {
                $table->unsignedBigInteger('gestionnaire_id')->nullable()->after('commune_id');
            }
            if (!Schema::hasColumn('sites', 'coordinateur_id')) {
                $table->unsignedBigInteger('coordinateur_id')->nullable()->after('gestionnaire_id');
            }
            if (!Schema::hasColumn('sites', 'categorie_site_id')) {
                $table->unsignedBigInteger('categorie_site_id')->nullable()->after('coordinateur_id');
            }

            if (!Schema::hasColumn('sites', 'province')) {
                $table->string('province')->nullable()->after('categorie_site_id');
            }
            if (!Schema::hasColumn('sites', 'code_province')) {
                $table->string('code_province')->nullable()->after('province');
            }
            if (!Schema::hasColumn('sites', 'territoire')) {
                $table->string('territoire')->nullable()->after('code_province');
            }
            if (!Schema::hasColumn('sites', 'code_territoire')) {
                $table->string('code_territoire')->nullable()->after('territoire');
            }
            if (!Schema::hasColumn('sites', 'zone_sante')) {
                $table->string('zone_sante')->nullable()->after('code_territoire');
            }
            if (!Schema::hasColumn('sites', 'code_zone_sante')) {
                $table->string('code_zone_sante')->nullable()->after('zone_sante');
            }
            if (!Schema::hasColumn('sites', 'aire_sante')) {
                $table->string('aire_sante')->nullable()->after('code_zone_sante');
            }
            if (!Schema::hasColumn('sites', 'code_aire_sante')) {
                $table->string('code_aire_sante')->nullable()->after('aire_sante');
            }

            if (!Schema::hasColumn('sites', 'longitude')) {
                $table->decimal('longitude', 11, 7)->nullable()->after('code_aire_sante');
            }
            if (!Schema::hasColumn('sites', 'latitude')) {
                $table->decimal('latitude', 11, 7)->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('sites', 'photos')) {
                $table->json('photos')->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('sites', 'geojson_data')) {
                $table->json('geojson_data')->nullable()->after('photos');
            }
            if (!Schema::hasColumn('sites', 'geometry_type')) {
                $table->string('geometry_type', 50)->nullable()->after('geojson_data');
            }
            if (!Schema::hasColumn('sites', 'collection_accuracy_m')) {
                $table->decimal('collection_accuracy_m', 8, 2)->nullable()->after('geometry_type');
            }
            if (!Schema::hasColumn('sites', 'geometry_collected_at')) {
                $table->timestamp('geometry_collected_at')->nullable()->after('collection_accuracy_m');
            }

            if (!Schema::hasColumn('sites', 'menages')) {
                $table->integer('menages')->nullable()->after('geometry_collected_at');
            }
            if (!Schema::hasColumn('sites', 'individus')) {
                $table->integer('individus')->nullable()->after('menages');
            }
            if (!Schema::hasColumn('sites', 'f_0_5')) {
                $table->integer('f_0_5')->nullable()->after('individus');
            }
            if (!Schema::hasColumn('sites', 'f_6_17')) {
                $table->integer('f_6_17')->nullable()->after('f_0_5');
            }
            if (!Schema::hasColumn('sites', 'f_18_59')) {
                $table->integer('f_18_59')->nullable()->after('f_6_17');
            }
            if (!Schema::hasColumn('sites', 'f_60_plus')) {
                $table->integer('f_60_plus')->nullable()->after('f_18_59');
            }
            if (!Schema::hasColumn('sites', 'h_0_5')) {
                $table->integer('h_0_5')->nullable()->after('f_60_plus');
            }
            if (!Schema::hasColumn('sites', 'h_6_17')) {
                $table->integer('h_6_17')->nullable()->after('h_0_5');
            }
            if (!Schema::hasColumn('sites', 'h_18_59')) {
                $table->integer('h_18_59')->nullable()->after('h_6_17');
            }
            if (!Schema::hasColumn('sites', 'h_60_plus')) {
                $table->integer('h_60_plus')->nullable()->after('h_18_59');
            }

            if (!Schema::hasColumn('sites', 'source')) {
                $table->string('source')->nullable()->after('h_60_plus');
            }
            if (!Schema::hasColumn('sites', 'round')) {
                $table->string('round')->nullable()->after('source');
            }
            if (!Schema::hasColumn('sites', 'type_gestion')) {
                $table->string('type_gestion')->nullable()->after('round');
            }
            if (!Schema::hasColumn('sites', 'date_fermeture')) {
                $table->date('date_fermeture')->nullable()->after('type_gestion');
            }
            if (!Schema::hasColumn('sites', 'raison_fermeture')) {
                $table->text('raison_fermeture')->nullable()->after('date_fermeture');
            }
            if (!Schema::hasColumn('sites', 'commentaire_fermeture')) {
                $table->text('commentaire_fermeture')->nullable()->after('raison_fermeture');
            }
            if (!Schema::hasColumn('sites', 'document_fermeture')) {
                $table->string('document_fermeture')->nullable()->after('commentaire_fermeture');
            }
            if (!Schema::hasColumn('sites', 'date_mise_a_jour')) {
                $table->date('date_mise_a_jour')->nullable()->after('document_fermeture');
            }
            if (!Schema::hasColumn('sites', 'type_fichier')) {
                $table->string('type_fichier')->nullable()->after('date_mise_a_jour');
            }
        });
    }

    public function down(): void
    {
        // Migration de restauration: pas de rollback destructif automatique.
    }
};
