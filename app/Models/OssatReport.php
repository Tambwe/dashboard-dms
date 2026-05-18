<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OssatReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'start_time','end_time','today','deviceid','phonenumber',
        'enumerator_name','fait_partie_agence','agence_enqueteur','autre_agence','nouveau_site',
        // Section 2
        'province_id','territoire_id','site_id','site_code','site_nom',
        'type_installation','propriete_fonciere','type_installation_detail',
        // Section 3
        'gps_latitude','gps_longitude','gps_altitude','date_mise_a_jour',
        // Section 4
        'statut',
        // Section 5
        'agence_gestion','agence_gestion_nom','agence_gestion_autre',
        'gestionnaire_dedie','gestionnaire_nom','gestionnaire_sexe',
        'gestionnaire_telephone','gestionnaire_email','gestionnaire_accepte_partage',
        // Section 6
        'agence_admin','agence_admin_nom','agence_admin_autre',
        'admin_dedie','admin_nom','admin_sexe','admin_telephone','admin_email','admin_accepte_partage',
        // Section 6b
        'agence_coord','agence_coord_nom',
        // Section 7
        'bureau_dedie','nb_hommes_staff','nb_femmes_staff',
        'presence_comite','comites','autres_comites','comites_elus',
        'comites_fonctionnels','nb_comites_fonctionnels','comites_formes','nb_comites_formes',
        'reunions_coordination','periodicite_reunions',
        'equipe_mobile_soutien','equipe_mobile','equipe_mobile_autre',
        'info_source','info_source_autre','cci','mgp',
        // Section 8
        'pdi_nouvelles_arrivees','pdi_nouvelles_qtite','pdi_retours','pdi_retours_qtite',
        'raisons_retours','autre_raison_retour',
        // Population
        'nb_familles','nb_individus',
        'h_0_4','f_0_4','h_5_17','f_5_17','h_18_59','f_18_59','h_60plus','f_60plus',
        // Section 9
        'menages_femme_chef','menages_enfant_chef','enfants_non_accompagnes',
        'handicap_physique','handicap_mental','maladies_chroniques_nb','personnes_agees_isolees',
        // Section 10
        'capacite_accueil','familles_attente','reduction_prevue',
        // Section 11
        'types_abri','autre_type_abri',
        'bache_installees','bache_occupees','bache_maintenance','bache_remplacement',
        'materiaux_installes','materiaux_occupes','materiaux_maintenance','materiaux_remplacement',
        'planches_installees','planches_occupees','planches_maintenance','planches_remplacement',
        'feuilles_installees','feuilles_occupees','feuilles_maintenance','feuilles_remplacement',
        'construites_nb','construites_maintenance',
        'fortune_installees','fortune_occupees','fortune_maintenance','fortune_remplacement',
        'autres_abris_nb','autres_abris_occupes','autres_abris_maintenance','autres_abris_remplacement',
        // Section 12
        'ame_prioritaires','ame_prioritaires_autre','ame_harmattan','ame_harmattan_autre',
        'ame_saison_seche','ame_saison_seche_autre',
        // Section 13
        'strategies_ame','strategies_ame_autre',
        // Section 14
        'etat_parcelles','etat_routes','etat_canaux','risque_inondation',
        'nb_incendies','mesures_incendie','autres_problemes',
        // Section 15
        'eclairage_existant','sources_electricite',
        // Section 16
        'litres_eau_jour','sources_eau','autre_source_eau','qualite_eau','jours_sans_eau',
        'defecation_plein_air','savon_disponible','inondations_6mois','methode_elimination_dechets',
        'types_latrines','nb_latrines','types_douches','nb_douches','douches_separees',
        'latrines_vidangees','date_derniere_vidange','eclairage_latrines','wash_adapte_handicapes',
        // Section 17
        'problemes_sante','autre_probleme_sante','soin_sante_fonctionnel','soin_sante_interieur',
        'distance_soin_sante','services_urgences','services_chirurgicaux','services_pediatriques',
        'services_prenataux','ambulance','problemes_acces_sante','autre_probleme_acces_sante',
        'enfants_non_vaccines',
        // Section 19
        'repas_par_jour','defis_alimentation','stockage_magasin','autre_defi_alimentation',
        'regularite_assistance_alimentaire',
        // Section 20
        'restrictions_mouvement','types_restrictions','autre_restriction',
        'tensions_communaute','incidents_securitaires','acteurs_incidents','autre_incident','nature_incident',
        'sentiment_securite','menaces_site','autre_menace',
        'zones_dangereuses_femmes','autre_zone_femmes','zones_dangereuses_hommes','autre_zone_hommes',
        'services_handicapes','types_support_psy','autre_support_psy',
        'familles_sans_documents','distance_tribunaux','acces_tribunaux',
        // Section 21
        'ecole_primaire_presente','distance_ecole_primaire','ecole_secondaire_presente',
        'distance_ecole_secondaire','nb_enfants_scolarises','obstacles_education',
        'autre_obstacle_education','education_informelle','nb_enfants_education_informelle',
        // Section 22
        'marche_interieur','distance_marche','articles_non_disponibles','autres_articles',
        'sources_subsistance','autres_sources_subsistance','nb_familles_avec_revenu',
        'nb_jeunes_travaillant','enclos_betail',
        // Section 23
        'besoin_prioritaire_1','besoin_prioritaire_2','besoin_prioritaire_3',
        // Section 24
        'acces_education','acces_vivres','acces_sante','acces_sante_mentale','acces_subsistance',
        'acces_cash','acces_nfi','acces_nutrition','acces_protection','acces_abri','acces_wash','acces_dechets',
        // Section 25
        'partenaires_protection_presence','partenaires_protection_autre',
        'partenaires_gbv_presence','partenaires_gbv_autre',
        'partenaires_enfance_presence','partenaires_enfance_autre',
        'partenaires_education_presence','partenaires_education_autre',
        'partenaires_abri_presence','partenaires_abri_autre',
        'partenaires_eau_presence','partenaires_eau_autre',
        'partenaires_assainissement_presence','partenaires_assainissement_autre',
        'partenaires_dechets_presence','partenaires_dechets_autre',
        'partenaires_sante_primaire_presence','partenaires_sante_primaire_autre',
        'partenaires_sante_secondaire_presence','partenaires_sante_secondaire_autre',
        'partenaires_mhpss_presence','partenaires_mhpss_autre',
        'partenaires_nutrition_presence','partenaires_nutrition_autre',
        'partenaires_alimentaire_presence','partenaires_alimentaire_autre',
        'partenaires_cohesion_presence','partenaires_cohesion_autre',
        'partenaires_subsistance_presence','partenaires_subsistance_autre',
        'partenaires_communication_presence','partenaires_communication_autre',
        // Suivi
        'created_by','statut_validation','valide_par','date_validation','commentaire_validation',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'today' => 'date',
        'date_mise_a_jour' => 'date',
        'date_derniere_vidange' => 'date',
        'date_validation' => 'datetime',
        'nouveau_site' => 'boolean',
        'fait_partie_agence' => 'boolean',
        'agence_gestion' => 'boolean',
        'gestionnaire_dedie' => 'boolean',
        'gestionnaire_accepte_partage' => 'boolean',
        'agence_admin' => 'boolean',
        'admin_dedie' => 'boolean',
        'admin_accepte_partage' => 'boolean',
        'agence_coord' => 'boolean',
        'bureau_dedie' => 'boolean',
        'presence_comite' => 'boolean',
        'comites_elus' => 'boolean',
        'reunions_coordination' => 'boolean',
        'equipe_mobile_soutien' => 'boolean',
        'cci' => 'boolean',
        'mgp' => 'boolean',
        'pdi_nouvelles_arrivees' => 'boolean',
        'pdi_retours' => 'boolean',
        'reduction_prevue' => 'boolean',
        'mesures_incendie' => 'boolean',
        'eclairage_existant' => 'boolean',
        'qualite_eau' => 'boolean',
        'defecation_plein_air' => 'boolean',
        'savon_disponible' => 'boolean',
        'inondations_6mois' => 'boolean',
        'douches_separees' => 'boolean',
        'latrines_vidangees' => 'boolean',
        'eclairage_latrines' => 'boolean',
        'wash_adapte_handicapes' => 'boolean',
        'soin_sante_fonctionnel' => 'boolean',
        'soin_sante_interieur' => 'boolean',
        'services_urgences' => 'boolean',
        'services_chirurgicaux' => 'boolean',
        'services_pediatriques' => 'boolean',
        'services_prenataux' => 'boolean',
        'ambulance' => 'boolean',
        'stockage_magasin' => 'boolean',
        'restrictions_mouvement' => 'boolean',
        'sentiment_securite' => 'boolean',
        'services_handicapes' => 'boolean',
        'acces_tribunaux' => 'boolean',
        'ecole_primaire_presente' => 'boolean',
        'ecole_secondaire_presente' => 'boolean',
        'education_informelle' => 'boolean',
        'marche_interieur' => 'boolean',
        'enclos_betail' => 'boolean',
        // partenaires présences
        'partenaires_protection_presence' => 'boolean',
        'partenaires_gbv_presence' => 'boolean',
        'partenaires_enfance_presence' => 'boolean',
        'partenaires_education_presence' => 'boolean',
        'partenaires_abri_presence' => 'boolean',
        'partenaires_eau_presence' => 'boolean',
        'partenaires_assainissement_presence' => 'boolean',
        'partenaires_dechets_presence' => 'boolean',
        'partenaires_sante_primaire_presence' => 'boolean',
        'partenaires_sante_secondaire_presence' => 'boolean',
        'partenaires_mhpss_presence' => 'boolean',
        'partenaires_nutrition_presence' => 'boolean',
        'partenaires_alimentaire_presence' => 'boolean',
        'partenaires_cohesion_presence' => 'boolean',
        'partenaires_subsistance_presence' => 'boolean',
        'partenaires_communication_presence' => 'boolean',
        // JSON
        'comites' => 'array',
        'equipe_mobile' => 'array',
        'info_source' => 'array',
        'raisons_retours' => 'array',
        'types_abri' => 'array',
        'ame_prioritaires' => 'array',
        'ame_harmattan' => 'array',
        'ame_saison_seche' => 'array',
        'strategies_ame' => 'array',
        'sources_electricite' => 'array',
        'sources_eau' => 'array',
        'types_latrines' => 'array',
        'types_douches' => 'array',
        'problemes_sante' => 'array',
        'problemes_acces_sante' => 'array',
        'defis_alimentation' => 'array',
        'types_restrictions' => 'array',
        'acteurs_incidents' => 'array',
        'menaces_site' => 'array',
        'zones_dangereuses_femmes' => 'array',
        'zones_dangereuses_hommes' => 'array',
        'types_support_psy' => 'array',
        'obstacles_education' => 'array',
        'articles_non_disponibles' => 'array',
        'sources_subsistance' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function territoire(): BelongsTo
    {
        return $this->belongsTo(Territoire::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }
}
