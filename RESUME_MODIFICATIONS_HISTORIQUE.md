# Résumé des Modifications du Système

## 🗺️ Affichage des données GeoJSON sur la carte (28 mars 2026)

### Dernière modification : Labels permanents avec properties du GeoJSON

#### Ajout de labels permanents intelligents
- **Fonctionnalité** : Affichage du nom de chaque feature GeoJSON directement sur le polygone
- **Logique de priorité** : 
  1. `feature.properties.NOM` (priorité 1 - majuscules)
  2. `feature.properties.nom` (priorité 2 - minuscules)
  3. `feature.properties.name` (priorité 3 - anglais)
  4. Nom du site (fallback si pas de propriétés)
- **Implémentation** : Utilisation de `bindTooltip()` avec option `permanent: true` et `direction: 'center'`
- **Support** : Compatible avec les fichiers GeoJSON utilisant NOM en majuscules (ex: exports SIG)
- **Avantage** : Permet de différencier plusieurs zones d'un même site (ex: Zone A, Zone B, etc.)
- **Stylisation CSS personnalisée** :
  - Classe `.geojson-label` avec fond bleu (#3B82F6, opacité 0.9)
  - Bordure bleue foncée (#1E40AF, 2px)
  - Coins arrondis (6px)
  - Ombre portée pour meilleure lisibilité
  - Support mode sombre avec couleurs adaptées
  - Texte blanc, gras, taille 13px
  - Pas de flèche de tooltip (supprimée avec `::before`)
- **Avantage** : Identification instantanée des sites sans clic

### Modifications apportées

#### 1. **Vue Utilisateur : `/my/sites`**
- **Fichier modifié** : `resources/views/user/sites/index.blade.php`
- **Fonctionnalités ajoutées** :
  - Affichage automatique des données GeoJSON sur la carte interactive
  - Support de tous les types GeoJSON (Point, LineString, Polygon, MultiPolygon, etc.)
  - Zones bleues avec remplissage semi-transparent pour distinguer du GPS
  - Popups interactifs sur les zones GeoJSON avec détails du site
  - Badge visuel "GeoJSON" (bleu) sur les cartes de sites
  - **Labels permanents affichant le nom du site sur chaque polygone**

#### 2. **Intégration Leaflet.js**
- Les sites avec `geojson_data` sont automatiquement rendus via `L.geoJSON()`
- Style personnalisé :
  - Bordure : Bleu primaire (#3B82F6, épaisseur 3px, opacité 0.8)
  - Remplissage : Bleu clair (#60A5FA, opacité 0.3)
- Points GeoJSON convertis en `circleMarker` avec style cohérent
- Gestion des erreurs avec `try/catch` et logs console

#### 3. **Logique d'affichage**
- **Sites avec GPS uniquement** : Marqueur rouge/orange
- **Sites avec GeoJSON uniquement** : Zone bleue sans marqueur
- **Sites avec GPS + GeoJSON** : Marqueur ET zone bleue combinés
- Condition de chargement étendue : `@if($site->latitude && $site->longitude || $site->geojson_data)`

#### 4. **Documentation mise à jour**
- **MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md** :
  - Section "Utiliser la carte interactive" enrichie avec GeoJSON
  - Nouvelle FAQ Q11 expliquant le fonctionnement des zones GeoJSON
  - Badges visuels documentés (GPS vert, GeoJSON bleu, Assigné violet)
- **ATTRIBUTION_SITES_UTILISATEURS.md** :
  - Section "Mes Sites" mise à jour avec détails techniques Leaflet
  - Spécifications d'affichage et style personnalisé documentés

### Avantages utilisateur

✅ **Visualisation complète** : Les limites, zones et périmètres des sites sont désormais visibles  
✅ **Interaction intuitive** : Cliquez sur les zones pour voir les détails  
✅ **Identification intelligente** : Chaque feature affiche son propre nom (properties.nom ou properties.name)  
✅ **Différenciation des zones** : Permet de distinguer plusieurs zones d'un même site (Zone A, Zone B, etc.)  
✅ **Badge "GeoJSON"** : Badge visuel sur les cartes de sites pour identification rapide  
✅ **Flexibilité** : Support GPS seul, GeoJSON seul, ou combinaison des deux  
✅ **Performance** : Pas d'impact sur les temps de chargement  
✅ **Lisibilité optimale** : Labels avec fond coloré et ombre pour bonne visibilité sur tous les fonds de carte  

---

## 📊 Système d'historique des mouvements de population

## ✅ Modifications effectuées

### 1. **Base de données**

#### Nouvelle table : `site_mouvements_population`
- **Objectif** : Enregistrer tous les mouvements de population (arrivées, départs, ajustements, recensements)
- **Champs principaux** :
  - `site_id` : Référence au site
  - `date_mouvement` : Date du mouvement
  - `type_mouvement` : Type (arrivee, depart, ajustement, recensement)
  - `periode` : Période de référence (ex: 2026-03)
  - Tous les champs démographiques (menages, individus, f_0_5, etc.)
  - `raison`, `description`, `source`, `round`
  - `created_by` : Utilisateur ayant créé le mouvement
- **Migration** : `2026_03_27_015635_create_site_mouvements_population_table.php`

#### Migration des données existantes
- **Migration** : `2026_03_27_015817_migrate_existing_site_data_to_mouvements.php`
- **Résultat** : 395 mouvements de type "recensement" créés à partir des données existantes
- Toutes les données démographiques actuelles des sites ont été copiées comme historique initial

### 2. **Modèles Eloquent**

#### Nouveau modèle : `SiteMouvementPopulation`
- **Fichier** : `app/Models/SiteMouvementPopulation.php`
- **Relations** :
  - `site()` : Relation avec le site
  - `createdBy()` : Relation avec l'utilisateur créateur
- **Attributs calculés** :
  - `total_femmes` : Somme de tous les groupes d'âge féminins
  - `total_hommes` : Somme de tous les groupes d'âge masculins
  - `totaux_coherents` : Vérifie que individus = femmes + hommes
- **Scopes** :
  - `deType($type)` : Filtrer par type de mouvement
  - `pourPeriode($debut, $fin)` : Filtrer par période
  - `arrivees()` : Uniquement les arrivées
  - `departs()` : Uniquement les départs
  - `recensements()` : Uniquement les recensements

#### Mise à jour du modèle `Site`
- **Ajout** : Relation `mouvementsPopulation()` pour accéder à l'historique
- **Usage** : `$site->mouvementsPopulation` retourne tous les mouvements du site

### 3. **Contrôleur API**

#### Nouveau contrôleur : `SiteMouvementPopulationController`
- **Fichier** : `app/Http/Controllers/SiteMouvementPopulationController.php`
- **Méthodes** :
  - `index()` : Lister les mouvements (avec filtrage par site)
  - `store()` : Créer un nouveau mouvement
  - `show($id)` : Afficher un mouvement
  - `statistics($siteId)` : Statistiques de mouvements pour un site

### 4. **Routes API**

Nouvelles routes sous `/api/mouvements-population` (nécessitent authentification) :
- `GET /api/mouvements-population` : Liste des mouvements
- `POST /api/mouvements-population` : Créer un mouvement
- `GET /api/mouvements-population/{id}` : Détails d'un mouvement
- `GET /api/mouvements-population/site/{siteId}/statistics` : Statistiques d'un site

### 5. **Documentation**

- **HISTORIQUE_POPULATION.md** : Guide complet d'utilisation du système
  - Exemples de code pour enregistrer des mouvements
  - Requêtes utiles
  - Bonnes pratiques

## 📊 Comment ça fonctionne

### Principe de base

**Avant** : Les données démographiques étaient stockées uniquement dans la table `sites` (écrasées à chaque mise à jour)

**Maintenant** : 
1. La table `sites` garde l'**état actuel** de la population
2. La table `site_mouvements_population` garde l'**historique complet**
3. Chaque changement est enregistré comme un mouvement

### Types de mouvements

1. **Arrivée** (`arrivee`) : Nouvelles personnes
   - Valeurs **positives**
   - Exemple : +200 individus, +50 ménages
   
2. **Départ** (`depart`) : Personnes qui partent
   - Valeurs **négatives**
   - Exemple : -80 individus, -20 ménages
   
3. **Ajustement** (`ajustement`) : Correction de données
   - Valeurs positives ou négatives
   - Exemple : +5 individus (correction d'erreur)
   
4. **Recensement** (`recensement`) : Comptage complet
   - Valeurs **absolues** (état total)
   - Exemple : 2000 individus, 500 ménages

### Workflow typique

```
1. Mouvement enregistré dans site_mouvements_population
                    ↓
2. Table sites mise à jour automatiquement
                    ↓
3. Historique complet préservé
```

## 🚀 Exemples d'utilisation

### Enregistrer une arrivée via API

```bash
POST /api/mouvements-population
Content-Type: application/json
Authorization: Bearer {token}

{
  "site_id": 1,
  "date_mouvement": "2026-03-27",
  "type_mouvement": "arrivee",
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
  "raison": "Déplacement suite à conflit",
  "source": "DTM Round 15"
}
```

### Consulter l'historique d'un site via Eloquent

```php
$site = Site::find(1);

// Tous les mouvements
$mouvements = $site->mouvementsPopulation()->orderBy('date_mouvement')->get();

// Mouvements du dernier mois
$mouvements = $site->mouvementsPopulation()
    ->pourPeriode(now()->subMonth(), now())
    ->get();

// Uniquement les arrivées
$arrivees = $site->mouvementsPopulation()->arrivees()->get();

// Calculer le total d'arrivées
$totalArrivees = $site->mouvementsPopulation()->arrivees()->sum('individus');
```

### Obtenir les statistiques d'un site via API

```bash
GET /api/mouvements-population/site/1/statistics?date_debut=2026-01-01&date_fin=2026-03-31
```

Réponse :
```json
{
  "site": {
    "id": 1,
    "nom": "BUGARULA CENTRE",
    "code_site": "KV001"
  },
  "population_actuelle": {
    "menages": 600,
    "individus": 2400
  },
  "mouvements": {
    "total": 5,
    "recensements": 1,
    "arrivees": {
      "nombre": 3,
      "total_individus": 500
    },
    "departs": {
      "nombre": 1,
      "total_individus": -100
    }
  },
  "periode": {
    "debut": "2026-01-01",
    "fin": "2026-03-31"
  }
}
```

## ✅ Vérification du système

Le système a été testé et fonctionne correctement :

- ✅ 395 mouvements initiaux créés (recensements)
- ✅ Relations Eloquent fonctionnelles
- ✅ Scopes opérationnels
- ✅ Attributs calculés corrects
- ✅ Cohérence des données vérifiée
- ✅ API fonctionnelle

## 📝 Prochaines étapes recommandées

1. **Interface utilisateur** :
   - Créer un formulaire pour enregistrer les mouvements
   - Créer une page pour visualiser l'historique d'un site
   - Ajouter des graphiques d'évolution

2. **Rapports** :
   - Rapport mensuel des mouvements
   - Graphique d'évolution de la population
   - Tableau de bord des tendances

3. **Notifications** :
   - Alertes sur les mouvements importants
   - Notifications par email pour les gestionnaires

4. **Import automatique** :
   - Script pour importer les données DTM
   - Création automatique de mouvements lors de l'import de nouveaux CSV

5. **Validation** :
   - Vérification automatique de cohérence des données
   - Alertes sur les anomalies

## 📚 Documentation

Consultez **HISTORIQUE_POPULATION.md** pour :
- Guide complet d'utilisation
- Tous les exemples de code
- Relations Eloquent détaillées
- Requêtes avancées
- Bonnes pratiques

## 🎯 Résultat

Le système permet maintenant de :
- ✅ Garder l'historique complet des mouvements de population
- ✅ Tracer les arrivées et départs
- ✅ Suivre l'évolution dans le temps
- ✅ Générer des statistiques et rapports
- ✅ Identifier les tendances
- ✅ Audit trail complet (qui a enregistré quoi et quand)

**La table `sites` continue de fonctionner normalement avec les données actuelles, et maintenant vous avez en plus l'historique complet dans `site_mouvements_population`.**
