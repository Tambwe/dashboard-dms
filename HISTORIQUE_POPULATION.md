# Système d'Historique des Mouvements de Population

## Vue d'ensemble

Le système d'historique des mouvements de population a été mis en place pour permettre de suivre l'évolution de la population dans chaque site au fil du temps.

## Architecture

### Table `site_mouvements_population`

Cette table enregistre tous les mouvements de population pour chaque site :

**Champs principaux :**
- `site_id` : Référence au site concerné
- `date_mouvement` : Date du mouvement
- `type_mouvement` : Type de mouvement
  - `arrivee` : Nouvelles personnes arrivant sur le site (valeurs positives)
  - `depart` : Personnes quittant le site (valeurs négatives)
  - `ajustement` : Correction de données (valeurs positives ou négatives)
  - `recensement` : Comptage complet de la population (valeurs absolues)
- `periode` : Période de référence (ex: "2026-03", "Q1-2026")
- Champs démographiques : `menages`, `individus`, `f_0_5`, `f_6_17`, `f_18_59`, `f_60_plus`, `h_0_5`, `h_6_17`, `h_18_59`, `h_60_plus`
- Métadonnées : `raison`, `description`, `source`, `round`, `created_by`

### Table `sites`

La table sites conserve les données démographiques actuelles. Ces données représentent l'état actuel de la population et peuvent être calculées en sommant tous les mouvements.

## Comment utiliser le système

### 1. Enregistrer une arrivée de population

```php
use App\Models\Site;
use App\Models\SiteMouvementPopulation;

$site = Site::find(1);

// Enregistrer l'arrivée de nouvelles personnes
$mouvement = SiteMouvementPopulation::create([
    'site_id' => $site->id,
    'date_mouvement' => '2026-03-27',
    'type_mouvement' => 'arrivee',
    'periode' => '2026-03',
    'menages' => 50,
    'individus' => 200,
    'f_0_5' => 25,
    'f_6_17' => 30,
    'f_18_59' => 35,
    'f_60_plus' => 10,
    'h_0_5' => 20,
    'h_6_17' => 35,
    'h_18_59' => 40,
    'h_60_plus' => 5,
    'raison' => 'Déplacement suite à conflit',
    'source' => 'DTM Round 15',
    'created_by' => auth()->id(),
]);

// Mettre à jour les totaux du site
$site->menages += $mouvement->menages;
$site->individus += $mouvement->individus;
$site->f_0_5 += $mouvement->f_0_5;
$site->f_6_17 += $mouvement->f_6_17;
$site->f_18_59 += $mouvement->f_18_59;
$site->f_60_plus += $mouvement->f_60_plus;
$site->h_0_5 += $mouvement->h_0_5;
$site->h_6_17 += $mouvement->h_6_17;
$site->h_18_59 += $mouvement->h_18_59;
$site->h_60_plus += $mouvement->h_60_plus;
$site->date_mise_a_jour = $mouvement->date_mouvement;
$site->save();
```

### 2. Enregistrer un départ de population

```php
// Enregistrer le départ de personnes (valeurs négatives)
$mouvement = SiteMouvementPopulation::create([
    'site_id' => $site->id,
    'date_mouvement' => '2026-03-27',
    'type_mouvement' => 'depart',
    'periode' => '2026-03',
    'menages' => -20,
    'individus' => -80,
    'f_0_5' => -10,
    'f_6_17' => -12,
    'f_18_59' => -15,
    'f_60_plus' => -3,
    'h_0_5' => -8,
    'h_6_17' => -14,
    'h_18_59' => -16,
    'h_60_plus' => -2,
    'raison' => 'Retour volontaire',
    'source' => 'Rapport gestionnaire',
    'created_by' => auth()->id(),
]);

// Mettre à jour les totaux du site (les valeurs sont déjà négatives)
$site->menages += $mouvement->menages;
$site->individus += $mouvement->individus;
// ... (même logique pour tous les champs)
$site->save();
```

### 3. Enregistrer un recensement complet

```php
// Lors d'un recensement complet, on enregistre les nouvelles valeurs totales
$mouvement = SiteMouvementPopulation::create([
    'site_id' => $site->id,
    'date_mouvement' => '2026-03-27',
    'type_mouvement' => 'recensement',
    'periode' => '2026-03',
    'menages' => 500,      // Valeur totale actuelle
    'individus' => 2000,   // Valeur totale actuelle
    'f_0_5' => 250,
    'f_6_17' => 300,
    'f_18_59' => 350,
    'f_60_plus' => 100,
    'h_0_5' => 200,
    'h_6_17' => 350,
    'h_18_59' => 400,
    'h_60_plus' => 50,
    'source' => 'DTM Round 15',
    'round' => '15',
    'created_by' => auth()->id(),
]);

// Remplacer les valeurs du site par les nouvelles valeurs du recensement
$site->update([
    'menages' => $mouvement->menages,
    'individus' => $mouvement->individus,
    'f_0_5' => $mouvement->f_0_5,
    'f_6_17' => $mouvement->f_6_17,
    'f_18_59' => $mouvement->f_18_59,
    'f_60_plus' => $mouvement->f_60_plus,
    'h_0_5' => $mouvement->h_0_5,
    'h_6_17' => $mouvement->h_6_17,
    'h_18_59' => $mouvement->h_18_59,
    'h_60_plus' => $mouvement->h_60_plus,
    'date_mise_a_jour' => $mouvement->date_mouvement,
]);
```

## Requêtes utiles

### Obtenir l'historique d'un site

```php
$site = Site::with('mouvementsPopulation')->find(1);

// Tous les mouvements
$mouvements = $site->mouvementsPopulation;

// Mouvements d'une période
$mouvements = $site->mouvementsPopulation()
    ->pourPeriode('2026-01-01', '2026-03-31')
    ->orderBy('date_mouvement')
    ->get();

// Uniquement les arrivées
$arrivees = $site->mouvementsPopulation()->arrivees()->get();

// Uniquement les départs
$departs = $site->mouvementsPopulation()->departs()->get();
```

### Calculer la population à une date donnée

```php
// Population totale jusqu'à une date
$populationHistorique = SiteMouvementPopulation::where('site_id', $site->id)
    ->where('date_mouvement', '<=', '2026-02-28')
    ->whereIn('type_mouvement', ['recensement', 'arrivee', 'depart', 'ajustement'])
    ->sum('individus');
```

### Statistiques sur les mouvements

```php
// Nombre total d'arrivées sur une période
$totalArrivees = SiteMouvementPopulation::where('site_id', $site->id)
    ->arrivees()
    ->pourPeriode('2026-01-01', '2026-03-31')
    ->sum('individus');

// Nombre total de départs sur une période
$totalDeparts = SiteMouvementPopulation::where('site_id', $site->id)
    ->departs()
    ->pourPeriode('2026-01-01', '2026-03-31')
    ->sum('individus');

// Solde net (arrivées - départs)
$soldeNet = $totalArrivees + $totalDeparts; // Les départs sont déjà négatifs
```

### Obtenir le dernier recensement

```php
$dernierRecensement = SiteMouvementPopulation::where('site_id', $site->id)
    ->recensements()
    ->latest('date_mouvement')
    ->first();
```

## Relations Eloquent

### Site → Mouvements

```php
$site = Site::find(1);
$mouvements = $site->mouvementsPopulation; // Tous les mouvements
```

### Mouvement → Site

```php
$mouvement = SiteMouvementPopulation::find(1);
$site = $mouvement->site;
```

### Mouvement → Utilisateur créateur

```php
$mouvement = SiteMouvementPopulation::find(1);
$utilisateur = $mouvement->createdBy;
```

## Attributs calculés

Le modèle `SiteMouvementPopulation` fournit des attributs calculés :

```php
$mouvement = SiteMouvementPopulation::find(1);

// Total des femmes
$totalFemmes = $mouvement->total_femmes;

// Total des hommes
$totalHommes = $mouvement->total_hommes;

// Vérifier la cohérence des totaux
if ($mouvement->totaux_coherents) {
    echo "Les totaux sont cohérents";
}
```

## Migration des données existantes

Lors de la mise en place du système, toutes les données démographiques existantes dans la table `sites` ont été copiées dans la table `site_mouvements_population` avec le type `recensement`. Cela constitue l'historique initial.

**Total : 395 mouvements initiaux créés**

## Prochaines étapes

1. **Interface utilisateur** : Créer des formulaires pour enregistrer les mouvements
2. **API** : Créer des endpoints pour les opérations CRUD sur les mouvements
3. **Rapports** : Créer des rapports d'évolution de la population
4. **Graphiques** : Visualiser les tendances d'évolution
5. **Validation** : Ajouter des règles de validation pour garantir la cohérence des données
6. **Notifications** : Alerter sur les mouvements importants de population

## Notes importantes

⚠️ **Important** :
- Pour les arrivées, utilisez des valeurs **positives**
- Pour les départs, utilisez des valeurs **négatives**
- Pour les recensements, utilisez les valeurs **absolues totales**
- Toujours mettre à jour la table `sites` après avoir enregistré un mouvement pour garder les données actuelles à jour
- Le champ `created_by` permet de tracer qui a enregistré chaque mouvement (audit trail)
