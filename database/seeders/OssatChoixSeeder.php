<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OssatChoixSeeder extends Seeder
{
    /**
     * Les listes de référence OSSAT (extraites du formulaire KoboToolbox).
     * Les groupes qui se recoupent partagent intentionnellement les mêmes valeurs
     * pour garantir la cohérence (ex: oui_non utilisé partout où c'est Oui/Non).
     */
    private array $choices = [
        // ── Oui / Non ─────────────────────────────────────────────────────────────
        'oui_non' => [
            'Oui',
            'Non',
        ],
        'yesno_pasdire' => [
            'Oui',
            'Non',
            'Pas dire',
        ],

        // ── Sexe ─────────────────────────────────────────────────────────────────
        'sexe' => [
            'Masculin',
            'Féminin',
        ],

        // ── Type d'installation ───────────────────────────────────────────────────
        'type_installation' => [
            'Camp',
            'ZAD',
            'Centre de transit',
            'Centre collectif',
            'Famille d\'accueil',
            'Autre',
        ],

        // ── Statut du site ───────────────────────────────────────────────────────
        'statut_site' => [
            'fonctionnel',
            'non_fonctionnel',
            'en_attente',
        ],

        // ── Propriété foncière ───────────────────────────────────────────────────
        'propriete' => [
            'Etat',
            'Communauté',
            'Privé',
            'Autre',
        ],

        // ── Périodicité ──────────────────────────────────────────────────────────
        'periodicite' => [
            'Hebdomadaire',
            'Bimensuelle',
            'Mensuelle',
            'Irrégulière',
        ],

        // ── État des infrastructures ─────────────────────────────────────────────
        'etat_infra' => [
            'Bon',
            'Moyen',
            'Mauvais',
            'Très mauvais',
        ],

        // ── Risque d'inondation ──────────────────────────────────────────────────
        'risque_inondation' => [
            'Jamais',
            'Rarement',
            'Parfois',
            'Souvent',
        ],

        // ── Repas par jour ───────────────────────────────────────────────────────
        'repas_par_jour' => [
            '< 1',
            '1',
            '2',
            '3+',
        ],

        // ── Régularité de l'aide alimentaire ────────────────────────────────────
        'regularite_aide' => [
            'Régulièrement',
            'Irrégulièrement',
            'Rarement',
            'Jamais',
        ],

        // ── Niveau d'accès aux services ──────────────────────────────────────────
        'acces' => [
            'Bon',
            'Limité',
            'Très limité',
            'Inexistant',
        ],

        // ── Méthodes d'élimination des déchets ──────────────────────────────────
        'elimination_dechets' => [
            'Brûlage',
            'Enfouissement',
            'Collecte organisée',
            'Décharge libre',
            'Compostage',
            'Autre',
        ],

        // ── Types de comités ─────────────────────────────────────────────────────
        'comites' => [
            'Comité de gestion de site',
            'Comité de femmes',
            'Comité de jeunes',
            'Comité de protection',
            'Comité WASH',
            'Comité de distribution',
            'Autre comité',
        ],

        // ── Types d'abri ─────────────────────────────────────────────────────────
        'types_abri' => [
            'Tente bâche',
            'Abri matériaux',
            'Abri planches',
            'Tente feuilles (RHU)',
            'Unité construite',
            'Abri de fortune',
            'Autre',
        ],

        // ── Sources d'eau ─────────────────────────────────────────────────────────
        'sources_eau' => [
            'Robinet',
            'Forage',
            'Puits',
            'Rivière/Lac',
            'Camion citerne',
            'Eau de pluie',
            'Autre',
        ],

        // ── Types de latrines ────────────────────────────────────────────────────
        'types_latrines' => [
            'Fosse simple',
            'Fosse ventilée',
            'Chasse d\'eau',
            'Sanplat',
            'Autre',
        ],

        // ── Types de douches ─────────────────────────────────────────────────────
        'types_douches' => [
            'Cabine fermée',
            'Espace à ciel ouvert',
            'Autre',
        ],

        // ── Problèmes de santé répertoriés ───────────────────────────────────────
        'problemes_sante' => [
            'Paludisme',
            'Choléra',
            'Diarrhée',
            'Infections respiratoires',
            'Malnutrition',
            'VIH/SIDA',
            'Traumatisme',
            'Autre',
        ],

        // ── Défis alimentaires ───────────────────────────────────────────────────
        'defis_alimentation' => [
            'Manque de vivres',
            'Prix élevés',
            'Insécurité',
            'Accès difficile au marché',
            'Pas de revenus',
            'Autre',
        ],

        // ── Besoins prioritaires ─────────────────────────────────────────────────
        'besoin_prioritaire' => [
            'Abri',
            'Eau/Assainissement',
            'Nourriture',
            'Santé',
            'Protection',
            'Education',
            'Moyens de subsistance',
            'NFI/AME',
            'Documentation',
            'Autre',
        ],

        // ── Obstacles à l'éducation ──────────────────────────────────────────────
        'obstacles_education' => [
            'Distance',
            'Coût',
            'Insécurité',
            'Manque d\'espace',
            'Travail des enfants',
            'Mariage précoce',
            'Documents manquants',
            'Autre',
        ],

        // ── Sources de subsistance ───────────────────────────────────────────────
        'sources_subsistance' => [
            'Agriculture',
            'Elevage',
            'Commerce',
            'Travail journalier',
            'Aide humanitaire',
            'Transferts familiaux',
            'Mendicité',
            'Autre',
        ],

        // ── Articles ménagers essentiels (AME) ───────────────────────────────────
        'ame_base' => [
            'Jerricane',
            'Bâche',
            'Moustiquaire',
            'Couverture',
            'Natte',
            'Ustensiles de cuisine',
            'Savon',
            'Autre',
        ],

        // ── Sources d'information / canaux d'annonce des distributions ───────────
        'info_source' => [
            'Annonces publiques',
            'Comités de site',
            'Bouche à oreille',
            'Affiches',
            'SMS',
            'Radio',
            'Autre',
        ],

        // ── Zones dangereuses ────────────────────────────────────────────────────
        'zones_dangereuses' => [
            'Latrines',
            'Zones forestières',
            'Périmètre du site',
            'Point d\'eau',
            'Marchés',
            'Autre',
        ],

        // ── Types de soutien psychosocial ────────────────────────────────────────
        'types_support_psy' => [
            'Soutien psychosocial',
            'Santé mentale',
            'Espaces sûrs',
            'Activités récréatives',
            'Autre',
        ],

        // ── Types de restrictions de mouvement ───────────────────────────────────
        'types_restrictions' => [
            'Couvre-feu',
            'Contrôle des sorties',
            'Zones interdites',
            'Barrages routiers',
            'Autre',
        ],

        // ── Acteurs impliqués dans les incidents ─────────────────────────────────
        'acteurs_incidents' => [
            'Forces armées',
            'Groupes armés',
            'Population locale',
            'Autres déplacés',
            'Police',
            'Autre',
        ],

        // ── Menaces sur le site ──────────────────────────────────────────────────
        'menaces_site' => [
            'Violence sexuelle',
            'Vol',
            'Attaque armée',
            'Discrimination',
            'Harcèlement',
            'Exploitation',
            'Autre',
        ],

        // ── Sources d'électricité ────────────────────────────────────────────────
        'sources_electricite' => [
            'Groupe électrogène',
            'Panneaux solaires',
            'Réseau public',
            'Batteries',
            'Autre',
        ],

        // ── Oui / Non / Partiellement ─────────────────────────────────────────────
        'oui_non_partiel' => [
            'Oui',
            'Non',
            'Partiellement',
        ],

        // ── Raisons de retour / mouvement ────────────────────────────────────────
        'raisons_retours' => [
            'Amélioration sécurité',
            'Fin du conflit',
            'Réunification familiale',
            'Manque de moyens sur le site',
            'Pression communautaire',
            'Amnistie',
            'Autre',
        ],

        // ── Niveaux de problème d'accès aux soins ────────────────────────────────
        'problemes_acces_sante' => [
            'Distance trop grande',
            'Coût des soins',
            'Manque de médicaments',
            'Insécurité sur le trajet',
            'Barrière linguistique',
            'Discrimination',
            'Autre',
        ],

        // ── Équipes mobiles ──────────────────────────────────────────────────────
        'equipe_mobile' => [
            'UNHCR',
            'HCR',
            'NRC',
            'IRC',
            'MSF',
            'UNICEF',
            'OIM',
            'Autre',
        ],
    ];

    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach ($this->choices as $groupe => $valeurs) {
            foreach ($valeurs as $ordre => $valeur) {
                $rows[] = [
                    'groupe'     => $groupe,
                    'valeur'     => $valeur,
                    'libelle'    => null,
                    'ordre'      => $ordre,
                    'actif'      => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Upsert pour ne pas dupliquer en cas de re-seed
        DB::table('ossat_choices')->upsert(
            $rows,
            ['groupe', 'valeur'],
            ['libelle', 'ordre', 'actif', 'updated_at']
        );
    }
}
