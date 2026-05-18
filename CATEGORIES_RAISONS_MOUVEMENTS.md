# Système de Catégorisation des Mouvements de Population

## Vue d'ensemble

Un système structuré de catégories et raisons a été mis en place pour mieux classifier et comprendre les mouvements de population dans les sites de déplacement.

## Architecture

### 1. Table `categorie_mouvements`

Définit les deux grandes catégories de mouvements :

| ID | Code    | Nom              | Description                              |
|----|---------|------------------|------------------------------------------|
| 1  | ENTREE  | nouvelle entree  | Nouvelles personnes arrivant sur le site |
| 2  | SORTIE  | sortie           | Personnes quittant le site (retour)      |

### 2. Table `raison_mouvements`

Définit les raisons spécifiques pour chaque catégorie :

#### Raisons pour "nouvelle entree" (Catégorie 1)

| ID | Code        | Nom                            | Description                                    |
|----|-------------|--------------------------------|------------------------------------------------|
| 1  | INSEC_ORIG  | insecurite zone d'origine      | Déplacement dû à l'insécurité dans la zone d'origine |
| 2  | FUSION      | fusion avec un autre site      | Fusion de plusieurs sites                      |
| 3  | DEPL_SITE   | deplacement dans un autre site | Déplacement vers un autre site PDI             |

#### Raisons pour "sortie" (Catégorie 2)

| ID | Code       | Nom                      | Description                                    |
|----|------------|--------------------------|------------------------------------------------|
| 4  | FERM_SITE  | fermeture d'un site      | Fermeture officielle du site                   |
| 5  | RET_VOL    | retour volontaire        | Retour volontaire dans la zone d'origine       |
| 6  | RET_FORCE  | retour force             | Retour forcé dans la zone d'origine            |
| 7  | DEMANT     | demantelement du site    | Démantèlement du site par les autorités        |

### 3. Modification de `site_mouvements_population`

Un nouveau champ a été ajouté :
- `raison_mouvement_id` : Clé étrangère vers `raison_mouvements`

**Note** : L'ancien champ `raison` (texte libre) est conservé pour rétrocompatibilité mais doit maintenant utiliser `raison_mouvement_id`.

## Données migrées

**Tous les 395 mouvements existants** ont été automatiquement assignés à la raison **"insecurite zone d'origine"** (ID: 1, Code: INSEC_ORIG).

## Relations Eloquent

### CategorieMouvement ↔ RaisonMouvement

```php
// Une catégorie a plusieurs raisons
$categorie = CategorieMouvement::find(1);
$raisons = $categorie->raisonMouvements;

// Une raison appartient à une catégorie
$raison = RaisonMouvement::find(1);
$categorie = $raison->categorieMouvement;
```

### RaisonMouvement ↔ SiteMouvementPopulation

```php
// Une raison est utilisée dans plusieurs mouvements
$raison = RaisonMouvement::find(1);
$mouvements = $raison->mouvementsPopulation;

// Un mouvement a une raison
$mouvement = SiteMouvementPopulation::find(1);
$raison = $mouvement->raisonMouvement;
$categorie = $mouvement->raisonMouvement->categorieMouvement;
```

## API Endpoints

### Récupérer toutes les catégories avec leurs raisons

```bash
GET /api/mouvements/categories
```

**Réponse** :
```json
[
  {
    "id": 1,
    "name": "nouvelle entree",
    "code": "ENTREE",
    "description": "Nouvelles personnes arrivant sur le site",
    "raison_mouvements": [
      {
        "id": 1,
        "name": "insecurite zone d'origine",
        "code": "INSEC_ORIG",
        "description": "Déplacement dû à l'insécurité..."
      },
      // ...
    ]
  },
  // ...
]
```

### Récupérer toutes les raisons

```bash
GET /api/mouvements/raisons
```

**Avec filtrage par catégorie** :
```bash
GET /api/mouvements/raisons?categorie_id=1
```

**Réponse** :
```json
[
  {
    "id": 1,
    "categorie_mouvement_id": 1,
    "name": "insecurite zone d'origine",
    "code": "INSEC_ORIG",
    "description": "Déplacement dû à l'insécurité dans la zone d'origine",
    "categorie_mouvement": {
      "id": 1,
      "name": "nouvelle entree",
      "code": "ENTREE"
    }
  },
  // ...
]
```

### Créer un mouvement avec raison

```bash
POST /api/mouvements-population
Content-Type: application/json
Authorization: Bearer {token}

{
  "site_id": 1,
  "date_mouvement": "2026-03-27",
  "type_mouvement": "arrivee",
  "raison_mouvement_id": 1,  // insecurite zone d'origine
  "periode": "2026-03",
  "menages": 50,
  "individus": 200,
  "f_0_5": 25,
  "f_6_17": 30,
  "f_18_59": 35,
  "f_60_plus": 10,
  "h_0_5": 20,
  "h_6_17": 35,
  "h_18_59": 40,
  "h_60_plus": 5,
  "description": "Arrivée massive suite à conflit armé",
  "source": "DTM Round 15"
}
```

## Scopes disponibles

### CategorieMouvement

```php
// Récupérer la catégorie "nouvelle entree"
$categorie = CategorieMouvement::nouvelleEntree()->first();

// Récupérer la catégorie "sortie"
$categorie = CategorieMouvement::sortie()->first();
```

### RaisonMouvement

```php
// Toutes les raisons de nouvelle entrée
$raisons = RaisonMouvement::nouvelleEntree()->get();

// Toutes les raisons de sortie
$raisons = RaisonMouvement::sortie()->get();

// Raisons d'une catégorie spécifique
$raisons = RaisonMouvement::pourCategorie(1)->get();
```

## Exemples d'utilisation

### 1. Sélectionner une raison dans un formulaire

```php
// Récupérer toutes les catégories avec leurs raisons
$categories = CategorieMouvement::with('raisonMouvements')->get();

// Dans une vue Blade
@foreach($categories as $categorie)
    <optgroup label="{{ $categorie->name }}">
        @foreach($categorie->raisonMouvements as $raison)
            <option value="{{ $raison->id }}">{{ $raison->name }}</option>
        @endforeach
    </optgroup>
@endforeach
```

### 2. Filtrer les mouvements par raison

```php
// Tous les mouvements dus à l'insécurité
$raisonInsecurite = RaisonMouvement::where('code', 'INSEC_ORIG')->first();
$mouvements = SiteMouvementPopulation::where('raison_mouvement_id', $raisonInsecurite->id)->get();

// Statistiques par raison
$stats = RaisonMouvement::withCount('mouvementsPopulation')->get();
```

### 3. Analyser les causes de mouvements

```php
// Nombre de mouvements par catégorie
$parCategorie = DB::table('site_mouvements_population')
    ->join('raison_mouvements', 'site_mouvements_population.raison_mouvement_id', '=', 'raison_mouvements.id')
    ->join('categorie_mouvements', 'raison_mouvements.categorie_mouvement_id', '=', 'categorie_mouvements.id')
    ->select('categorie_mouvements.name', DB::raw('count(*) as total'))
    ->groupBy('categorie_mouvements.name')
    ->get();

// Raisons les plus fréquentes
$raisonsPrincipales = DB::table('site_mouvements_population')
    ->join('raison_mouvements', 'site_mouvements_population.raison_mouvement_id', '=', 'raison_mouvements.id')
    ->select('raison_mouvements.name', DB::raw('count(*) as total'))
    ->groupBy('raison_mouvements.name')
    ->orderByDesc('total')
    ->get();
```

### 4. Migration de données avec raison

```php
// Exemple : lors de l'import de nouveaux sites, assigner automatiquement une raison
$raisonDefaut = RaisonMouvement::where('code', 'INSEC_ORIG')->first();

foreach ($sitesCSV as $siteData) {
    SiteMouvementPopulation::create([
        'site_id' => $site->id,
        'date_mouvement' => $siteData['date'],
        'type_mouvement' => 'recensement',
        'raison_mouvement_id' => $raisonDefaut->id,
        // ... autres champs
    ]);
}
```

## Modèles créés

### CategorieMouvement
- **Fichier** : `app/Models/CategorieMouvement.php`
- **Table** : `categorie_mouvements`
- **Relations** : `hasMany` RaisonMouvement

### RaisonMouvement
- **Fichier** : `app/Models/RaisonMouvement.php`
- **Table** : `raison_mouvements`
- **Relations** : 
  - `belongsTo` CategorieMouvement
  - `hasMany` SiteMouvementPopulation

### SiteMouvementPopulation (mis à jour)
- **Fichier** : `app/Models/SiteMouvementPopulation.php`
- **Nouvelle relation** : `belongsTo` RaisonMouvement

## Migrations créées

1. **2026_03_27_021627_create_categorie_mouvements_table.php**
   - Crée la table `categorie_mouvements`
   - Insère les 2 catégories par défaut

2. **2026_03_27_021632_create_raison_mouvements_table.php**
   - Crée la table `raison_mouvements`
   - Insère les 7 raisons par défaut

3. **2026_03_27_021635_add_raison_id_to_site_mouvements_population_table.php**
   - Ajoute `raison_mouvement_id` à `site_mouvements_population`
   - Assigne tous les mouvements existants à "insecurite zone d'origine"

## Contrôleur mis à jour

**SiteMouvementPopulationController** :
- Validation de `raison_mouvement_id`
- Méthode `getCategories()` : Récupère toutes les catégories
- Méthode `getRaisons()` : Récupère toutes les raisons (filtrable)

## Routes API

```
GET  /api/mouvements/categories          → Liste des catégories
GET  /api/mouvements/raisons             → Liste des raisons
GET  /api/mouvements/raisons?categorie_id=1 → Raisons filtrées
POST /api/mouvements-population          → Créer un mouvement (avec raison_mouvement_id)
```

## Statistiques actuelles

- ✅ **2 catégories** créées
- ✅ **7 raisons** créées  
- ✅ **395 mouvements existants** migrés avec raison "insecurite zone d'origine"
- ✅ Toutes les relations fonctionnelles
- ✅ API opérationnelle

## Avantages de ce système

1. **Standardisation** : Raisons cohérentes et normalisées
2. **Analyse** : Facilite les statistiques et rapports
3. **Traçabilité** : Comprendre les causes de déplacements
4. **Reporting** : Génération de rapports par type de mouvement
5. **Extensibilité** : Facile d'ajouter de nouvelles raisons
6. **Multilingue** : Structure permet la traduction

## Prochaines étapes recommandées

1. **Interface utilisateur** :
   - Ajouter un sélecteur de raisons dans les formulaires
   - Afficher les statistiques par raison dans le dashboard

2. **Rapports** :
   - Rapport des causes principales de déplacements
   - Graphiques d'évolution par raison
   - Carte des mouvements par type

3. **Alertes** :
   - Notifications sur augmentation de certaines raisons
   - Alertes sur raisons critiques (démantèlement, etc.)

4. **Import automatique** :
   - Mapping automatique des raisons lors de l'import CSV
   - Détection de patterns dans les données sources

## Notes importantes

⚠️ **Important** :
- L'ancien champ `raison` (texte) est conservé mais **utilisez maintenant `raison_mouvement_id`**
- Toujours vérifier que `raison_mouvement_id` existe avant de créer un mouvement
- Les raisons sont liées aux catégories - vérifier la cohérence
- Les 395 mouvements existants ont tous la raison "insecurite zone d'origine" par défaut

## Consultation de la documentation

Pour plus d'informations sur le système d'historique de base, consultez :
- **HISTORIQUE_POPULATION.md** : Documentation du système d'historique
- **RESUME_MODIFICATIONS_HISTORIQUE.md** : Résumé des modifications

