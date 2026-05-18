<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ossat_reports', function (Blueprint $table) {
            $table->id();

            // ── Métadonnées collecte ──────────────────────────────────────
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->date('today')->nullable();
            $table->string('deviceid', 100)->nullable();
            $table->string('phonenumber', 30)->nullable();
            $table->string('enumerator_name', 150)->nullable();
            $table->boolean('fait_partie_agence')->nullable();
            $table->string('agence_enqueteur', 150)->nullable();
            $table->string('autre_agence', 150)->nullable();
            $table->boolean('nouveau_site')->nullable()->comment('true=nouveau, false=existant');

            // ── Section 2 : Localisation du site ─────────────────────────
            $table->unsignedBigInteger('province_id')->nullable();
            $table->unsignedBigInteger('territoire_id')->nullable();
            $table->string('site_code', 50)->nullable()->comment('SITE ID calculé');
            $table->string('site_nom', 150)->nullable();
            $table->string('type_installation', 50)->nullable()->comment('Camp/ZAD/Centre transit...');
            $table->string('propriete_fonciere', 50)->nullable();
            $table->string('type_installation_detail', 100)->nullable();

            // ── Section 3 : Géolocalisation ──────────────────────────────
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->decimal('gps_altitude', 8, 2)->nullable();
            $table->date('date_mise_a_jour')->nullable();

            // ── Section 4 : Statut ────────────────────────────────────────
            $table->string('statut', 30)->nullable()->comment('fonctionnel/non_fonctionnel/en_attente');

            // ── Section 5 : Gestion de site ───────────────────────────────
            $table->boolean('agence_gestion')->nullable();
            $table->string('agence_gestion_nom', 150)->nullable();
            $table->string('agence_gestion_autre', 150)->nullable();
            $table->boolean('gestionnaire_dedie')->nullable();
            $table->string('gestionnaire_nom', 150)->nullable();
            $table->string('gestionnaire_sexe', 20)->nullable();
            $table->string('gestionnaire_telephone', 30)->nullable();
            $table->string('gestionnaire_email', 150)->nullable();
            $table->boolean('gestionnaire_accepte_partage')->nullable();

            // ── Section 6 : Administration ────────────────────────────────
            $table->boolean('agence_admin')->nullable();
            $table->string('agence_admin_nom', 150)->nullable();
            $table->string('agence_admin_autre', 150)->nullable();
            $table->boolean('admin_dedie')->nullable();
            $table->string('admin_nom', 150)->nullable();
            $table->string('admin_sexe', 20)->nullable();
            $table->string('admin_telephone', 30)->nullable();
            $table->string('admin_email', 150)->nullable();
            $table->boolean('admin_accepte_partage')->nullable();

            // ── Section 6b : Coordination ────────────────────────────────
            $table->boolean('agence_coord')->nullable();
            $table->string('agence_coord_nom', 150)->nullable();

            // ── Section 7 : Organisation interne ─────────────────────────
            $table->boolean('bureau_dedie')->nullable();
            $table->integer('nb_hommes_staff')->nullable()->default(0);
            $table->integer('nb_femmes_staff')->nullable()->default(0);
            $table->boolean('presence_comite')->nullable();
            $table->json('comites')->nullable();
            $table->string('autres_comites', 200)->nullable();
            $table->boolean('comites_elus')->nullable();
            $table->string('comites_fonctionnels', 30)->nullable()->comment('oui/non/partiellement');
            $table->integer('nb_comites_fonctionnels')->nullable();
            $table->string('comites_formes', 30)->nullable()->comment('oui/non/partiellement');
            $table->integer('nb_comites_formes')->nullable();
            $table->boolean('reunions_coordination')->nullable();
            $table->string('periodicite_reunions', 30)->nullable();
            $table->boolean('equipe_mobile_soutien')->nullable();
            $table->json('equipe_mobile')->nullable();
            $table->string('equipe_mobile_autre', 150)->nullable();
            $table->json('info_source')->nullable();
            $table->string('info_source_autre', 150)->nullable();
            $table->boolean('cci')->nullable()->comment('Centre communautaire d\'information');
            $table->boolean('mgp')->nullable()->comment('Mécanisme gestion de plainte');

            // ── Section 8 : Mouvements de population ─────────────────────
            $table->boolean('pdi_nouvelles_arrivees')->nullable();
            $table->integer('pdi_nouvelles_qtite')->nullable();
            $table->boolean('pdi_retours')->nullable();
            $table->integer('pdi_retours_qtite')->nullable();
            $table->json('raisons_retours')->nullable();
            $table->string('autre_raison_retour', 150)->nullable();

            // ── Population : désagrégation âge/sexe ──────────────────────
            $table->integer('nb_familles')->nullable();
            $table->integer('nb_individus')->nullable();
            $table->integer('h_0_4')->nullable()->default(0);
            $table->integer('f_0_4')->nullable()->default(0);
            $table->integer('h_5_17')->nullable()->default(0);
            $table->integer('f_5_17')->nullable()->default(0);
            $table->integer('h_18_59')->nullable()->default(0);
            $table->integer('f_18_59')->nullable()->default(0);
            $table->integer('h_60plus')->nullable()->default(0);
            $table->integer('f_60plus')->nullable()->default(0);

            // ── Section 9 : Groupes spécifiques ──────────────────────────
            $table->integer('menages_femme_chef')->nullable()->default(0);
            $table->integer('menages_enfant_chef')->nullable()->default(0);
            $table->integer('enfants_non_accompagnes')->nullable()->default(0);
            $table->integer('handicap_physique')->nullable()->default(0);
            $table->integer('handicap_mental')->nullable()->default(0);
            $table->integer('maladies_chroniques_nb')->nullable()->default(0);
            $table->integer('personnes_agees_isolees')->nullable()->default(0);

            // ── Section 10 : Capacité d'accueil ──────────────────────────
            $table->integer('capacite_accueil')->nullable();
            $table->integer('familles_attente')->nullable()->default(0);
            $table->boolean('reduction_prevue')->nullable();

            // ── Section 11 : Types d'abris ────────────────────────────────
            $table->json('types_abri')->nullable();
            $table->string('autre_type_abri', 150)->nullable();
            // Tentes bâche
            $table->integer('bache_installees')->nullable()->default(0);
            $table->integer('bache_occupees')->nullable()->default(0);
            $table->integer('bache_maintenance')->nullable()->default(0);
            $table->integer('bache_remplacement')->nullable()->default(0);
            // Matériaux
            $table->integer('materiaux_installes')->nullable()->default(0);
            $table->integer('materiaux_occupes')->nullable()->default(0);
            $table->integer('materiaux_maintenance')->nullable()->default(0);
            $table->integer('materiaux_remplacement')->nullable()->default(0);
            // Planches
            $table->integer('planches_installees')->nullable()->default(0);
            $table->integer('planches_occupees')->nullable()->default(0);
            $table->integer('planches_maintenance')->nullable()->default(0);
            $table->integer('planches_remplacement')->nullable()->default(0);
            // Feuilles (RHU)
            $table->integer('feuilles_installees')->nullable()->default(0);
            $table->integer('feuilles_occupees')->nullable()->default(0);
            $table->integer('feuilles_maintenance')->nullable()->default(0);
            $table->integer('feuilles_remplacement')->nullable()->default(0);
            // Construites
            $table->integer('construites_nb')->nullable()->default(0);
            $table->integer('construites_maintenance')->nullable()->default(0);
            // Fortune
            $table->integer('fortune_installees')->nullable()->default(0);
            $table->integer('fortune_occupees')->nullable()->default(0);
            $table->integer('fortune_maintenance')->nullable()->default(0);
            $table->integer('fortune_remplacement')->nullable()->default(0);
            // Autres abris
            $table->integer('autres_abris_nb')->nullable()->default(0);
            $table->integer('autres_abris_occupes')->nullable()->default(0);
            $table->integer('autres_abris_maintenance')->nullable()->default(0);
            $table->integer('autres_abris_remplacement')->nullable()->default(0);

            // ── Section 12 : Besoins AME ──────────────────────────────────
            $table->json('ame_prioritaires')->nullable();
            $table->string('ame_prioritaires_autre', 150)->nullable();
            $table->json('ame_harmattan')->nullable();
            $table->string('ame_harmattan_autre', 150)->nullable();
            $table->json('ame_saison_seche')->nullable();
            $table->string('ame_saison_seche_autre', 150)->nullable();

            // ── Section 13 : Stratégies AME ───────────────────────────────
            $table->json('strategies_ame')->nullable();
            $table->string('strategies_ame_autre', 150)->nullable();

            // ── Section 14 : Entretien / problèmes ───────────────────────
            $table->string('etat_parcelles', 30)->nullable();
            $table->string('etat_routes', 30)->nullable();
            $table->string('etat_canaux', 30)->nullable();
            $table->string('risque_inondation', 30)->nullable();
            $table->integer('nb_incendies')->nullable()->default(0);
            $table->boolean('mesures_incendie')->nullable();
            $table->text('autres_problemes')->nullable();

            // ── Section 15 : Eclairage ────────────────────────────────────
            $table->boolean('eclairage_existant')->nullable();
            $table->json('sources_electricite')->nullable();

            // ── Section 16 : WASH ─────────────────────────────────────────
            $table->integer('litres_eau_jour')->nullable();
            $table->json('sources_eau')->nullable();
            $table->string('autre_source_eau', 150)->nullable();
            $table->boolean('qualite_eau')->nullable();
            $table->integer('jours_sans_eau')->nullable()->default(0);
            $table->boolean('defecation_plein_air')->nullable();
            $table->boolean('savon_disponible')->nullable();
            $table->boolean('inondations_6mois')->nullable();
            $table->string('methode_elimination_dechets', 50)->nullable();
            $table->json('types_latrines')->nullable();
            $table->integer('nb_latrines')->nullable()->default(0);
            $table->json('types_douches')->nullable();
            $table->integer('nb_douches')->nullable()->default(0);
            $table->boolean('douches_separees')->nullable();
            $table->boolean('latrines_vidangees')->nullable();
            $table->date('date_derniere_vidange')->nullable();
            $table->boolean('eclairage_latrines')->nullable();
            $table->boolean('wash_adapte_handicapes')->nullable();

            // ── Section 17 : Santé ────────────────────────────────────────
            $table->json('problemes_sante')->nullable();
            $table->string('autre_probleme_sante', 150)->nullable();
            $table->boolean('soin_sante_fonctionnel')->nullable();
            $table->boolean('soin_sante_interieur')->nullable();
            $table->string('distance_soin_sante', 50)->nullable();
            $table->boolean('services_urgences')->nullable();
            $table->boolean('services_chirurgicaux')->nullable();
            $table->boolean('services_pediatriques')->nullable();
            $table->boolean('services_prenataux')->nullable();
            $table->boolean('ambulance')->nullable();
            $table->json('problemes_acces_sante')->nullable();
            $table->string('autre_probleme_acces_sante', 150)->nullable();
            $table->integer('enfants_non_vaccines')->nullable()->default(0);

            // ── Section 19 : Sécurité alimentaire ────────────────────────
            $table->string('repas_par_jour', 20)->nullable();
            $table->json('defis_alimentation')->nullable();
            $table->boolean('stockage_magasin')->nullable();
            $table->string('autre_defi_alimentation', 150)->nullable();
            $table->string('regularite_assistance_alimentaire', 50)->nullable();

            // ── Section 20 : Protection ───────────────────────────────────
            $table->boolean('restrictions_mouvement')->nullable();
            $table->json('types_restrictions')->nullable();
            $table->string('autre_restriction', 150)->nullable();
            $table->string('tensions_communaute', 20)->nullable()->comment('oui/non/pas_dire');
            $table->string('incidents_securitaires', 20)->nullable()->comment('oui/non/pas_dire');
            $table->json('acteurs_incidents')->nullable();
            $table->string('autre_incident', 150)->nullable();
            $table->text('nature_incident')->nullable();
            $table->boolean('sentiment_securite')->nullable();
            $table->json('menaces_site')->nullable();
            $table->string('autre_menace', 150)->nullable();
            $table->json('zones_dangereuses_femmes')->nullable();
            $table->string('autre_zone_femmes', 150)->nullable();
            $table->json('zones_dangereuses_hommes')->nullable();
            $table->string('autre_zone_hommes', 150)->nullable();
            $table->boolean('services_handicapes')->nullable();
            $table->json('types_support_psy')->nullable();
            $table->string('autre_support_psy', 150)->nullable();
            $table->integer('familles_sans_documents')->nullable()->default(0);
            $table->string('distance_tribunaux', 50)->nullable();
            $table->boolean('acces_tribunaux')->nullable();

            // ── Section 21 : Education ────────────────────────────────────
            $table->boolean('ecole_primaire_presente')->nullable();
            $table->string('distance_ecole_primaire', 50)->nullable();
            $table->boolean('ecole_secondaire_presente')->nullable();
            $table->string('distance_ecole_secondaire', 50)->nullable();
            $table->integer('nb_enfants_scolarises')->nullable()->default(0);
            $table->json('obstacles_education')->nullable();
            $table->string('autre_obstacle_education', 150)->nullable();
            $table->boolean('education_informelle')->nullable();
            $table->integer('nb_enfants_education_informelle')->nullable()->default(0);

            // ── Section 22 : Moyens de subsistance ───────────────────────
            $table->boolean('marche_interieur')->nullable();
            $table->string('distance_marche', 50)->nullable();
            $table->json('articles_non_disponibles')->nullable();
            $table->string('autres_articles', 150)->nullable();
            $table->json('sources_subsistance')->nullable();
            $table->string('autres_sources_subsistance', 150)->nullable();
            $table->integer('nb_familles_avec_revenu')->nullable()->default(0);
            $table->integer('nb_jeunes_travaillant')->nullable()->default(0);
            $table->boolean('enclos_betail')->nullable();

            // ── Section 23 : Besoins prioritaires ────────────────────────
            $table->string('besoin_prioritaire_1', 50)->nullable();
            $table->string('besoin_prioritaire_2', 50)->nullable();
            $table->string('besoin_prioritaire_3', 50)->nullable();

            // ── Section 24 : Accès aux services ──────────────────────────
            $table->string('acces_education', 30)->nullable();
            $table->string('acces_vivres', 30)->nullable();
            $table->string('acces_sante', 30)->nullable();
            $table->string('acces_sante_mentale', 30)->nullable();
            $table->string('acces_subsistance', 30)->nullable();
            $table->string('acces_cash', 30)->nullable();
            $table->string('acces_nfi', 30)->nullable();
            $table->string('acces_nutrition', 30)->nullable();
            $table->string('acces_protection', 30)->nullable();
            $table->string('acces_abri', 30)->nullable();
            $table->string('acces_wash', 30)->nullable();
            $table->string('acces_dechets', 30)->nullable();

            // ── Section 25 : Cartographie des acteurs ─────────────────────
            $table->boolean('partenaires_protection_presence')->nullable();
            $table->string('partenaires_protection_autre', 200)->nullable();
            $table->boolean('partenaires_gbv_presence')->nullable();
            $table->string('partenaires_gbv_autre', 200)->nullable();
            $table->boolean('partenaires_enfance_presence')->nullable();
            $table->string('partenaires_enfance_autre', 200)->nullable();
            $table->boolean('partenaires_education_presence')->nullable();
            $table->string('partenaires_education_autre', 200)->nullable();
            $table->boolean('partenaires_abri_presence')->nullable();
            $table->string('partenaires_abri_autre', 200)->nullable();
            $table->boolean('partenaires_eau_presence')->nullable();
            $table->string('partenaires_eau_autre', 200)->nullable();
            $table->boolean('partenaires_assainissement_presence')->nullable();
            $table->string('partenaires_assainissement_autre', 200)->nullable();
            $table->boolean('partenaires_dechets_presence')->nullable();
            $table->string('partenaires_dechets_autre', 200)->nullable();
            $table->boolean('partenaires_sante_primaire_presence')->nullable();
            $table->string('partenaires_sante_primaire_autre', 200)->nullable();
            $table->boolean('partenaires_sante_secondaire_presence')->nullable();
            $table->string('partenaires_sante_secondaire_autre', 200)->nullable();
            $table->boolean('partenaires_mhpss_presence')->nullable();
            $table->string('partenaires_mhpss_autre', 200)->nullable();
            $table->boolean('partenaires_nutrition_presence')->nullable();
            $table->string('partenaires_nutrition_autre', 200)->nullable();
            $table->boolean('partenaires_alimentaire_presence')->nullable();
            $table->string('partenaires_alimentaire_autre', 200)->nullable();
            $table->boolean('partenaires_cohesion_presence')->nullable();
            $table->string('partenaires_cohesion_autre', 200)->nullable();
            $table->boolean('partenaires_subsistance_presence')->nullable();
            $table->string('partenaires_subsistance_autre', 200)->nullable();
            $table->boolean('partenaires_communication_presence')->nullable();
            $table->string('partenaires_communication_autre', 200)->nullable();

            // ── Suivi ─────────────────────────────────────────────────────
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('statut_validation', 20)->default('brouillon')->comment('brouillon/soumis/valide/rejete');
            $table->unsignedBigInteger('valide_par')->nullable();
            $table->timestamp('date_validation')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();
            $table->foreign('territoire_id')->references('id')->on('territoires')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('valide_par')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ossat_reports');
    }
};
