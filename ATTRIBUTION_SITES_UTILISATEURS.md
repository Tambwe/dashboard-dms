# Attribution Individuelle de Sites aux Utilisateurs

> 📋 **[Retour à l'index de la documentation](DOCUMENTATION.md)** | 📖 **[Consulter le Manuel Utilisateur complet](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md)**

## Vue d'ensemble

Ce système permet au **super administrateur** d'attribuer des sites spécifiques à des **utilisateurs individuels** pour la collecte de données, indépendamment de leur organisation. Cela permet une gestion granulaire des accès pour la collecte de données sur le terrain.

## Différences entre les 3 systèmes d'accès

### 1. Accès au niveau Organisation (`/organisation/sites`)
- **Pour qui** : Tous les membres d'une organisation
- **Attribution** : Un site est attribué à une organisation entière
- **Accès automatique** : Tous les utilisateurs de l'organisation peuvent voir et gérer les sites
- **Cas d'usage** : Gestion globale des sites par une organisation

### 2. Accès Individuel (`/my/sites`)
- **Pour qui** : Utilisateurs spécifiques choisis par l'admin
- **Attribution** : Un site est attribué individuellement à un utilisateur
- **Accès contrôlé** : Seuls les utilisateurs assignés peuvent voir ces sites
- **Cas d'usage** : **Collecte de données sur le terrain**, missions spécifiques, attributions temporaires

### 3. Accès Master List (`/sites/master-list`)
- **Pour qui** : Tous les utilisateurs authentifiés
- **Vue** : Lecture seule de tous les sites
- **Cas d'usage** : Consultation générale, exports, statistiques

## Fonctionnalités

### Pour les Super Administrateurs

#### 1. Vue d'ensemble des accès utilisateurs

**URL** : [/admin/user-site-access](http://127.0.0.1:8000/admin/user-site-access)

**Fonctionnalités** :
- Liste de tous les utilisateurs (sauf super admins)
- Filtres par :
  - Nom/Email (recherche)
  - Organisation
  - Rôle (utilisateur / admin organisation)
- Affichage du nombre de sites assignés à chaque utilisateur
- Accès rapide à la gestion des sites d'un utilisateur

#### 2. Gestion des sites d'un utilisateur

**URL** : `/admin/user-site-access/{user}/manage` (lien dynamique selon utilisateur)

**Fonctionnalités** :

##### Attribution unique
1. Sélectionner un site dans la liste déroulante
2. Définir les permissions :
   - ☑️ **Autoriser la modification** : L'utilisateur peut modifier GPS, photos, GeoJSON
   - ☑️ **Autoriser la collecte** : L'utilisateur peut collecter des données
3. Cliquer sur "Attribuer le site"

##### Attribution en masse
1. Cocher plusieurs sites dans la liste
2. Définir les permissions globales
3. Cliquer sur "Attribuer (X) sites"

##### Gestion des sites assignés
- **Liste des sites** : Voir tous les sites déjà assignés
- **Permissions en temps réel** : Modifier les permissions (modification/collecte) avec des cases à cocher
- **Retrait d'accès** : Retirer individuellement l'accès à un site
- **Informations** : Date d'attribution et qui a donné l'accès

### Pour les Utilisateurs

#### 1. Mes Sites

**URL** : [/my/sites](http://127.0.0.1:8000/my/sites)

Interface en cartes montrant :
- **Carte interactive** : Visualisation géographique avec Leaflet.js
  - Marqueurs rouges pour les sites avec coordonnées GPS
  - Zones bleues (polygones/lignes) pour les sites avec données GeoJSON
  - Popups interactifs avec détails et bouton "Gérer le site"
  - Auto-zoom pour afficher tous les sites
- **Badge GPS** : Si les coordonnées sont enregistrées
- **Badge GeoJSON** : Si le site contient des données géographiques complexes
- **Badge "Assigné"** : Sites assignés individuellement (vs sites de l'organisation)
- **Permissions** : Tags visuels montrant les droits (Modification / Collecte)
- **Informations** : Photos, localisation, population, type de site
- **Organisation** : À quelle organisation appartient le site

**Affichage GeoJSON sur la carte :**
- Les données `geojson_data` sont automatiquement rendues sur la carte avec `L.geoJSON()`
- Style personnalisé : bordure bleue (#3B82F6), remplissage bleu clair (#60A5FA) semi-transparent
- **Labels permanents** : Affichés directement sur chaque feature avec `bindTooltip()`
  - Priorité d'affichage : `feature.properties.NOM` > `feature.properties.nom` > `feature.properties.name` > nom du site
  - Support des champs en majuscules (NOM) et minuscules (nom, name)
  - Position centrale (`direction: 'center'`)
  - Fond bleu avec bordure et ombre
  - Classe CSS personnalisée : `.geojson-label`
  - Toujours visible (pas besoin de clic)
  - Permet de différencier plusieurs features d'un même site
- Popups sur les features GeoJSON avec nom du site et propriétés
- Support complet des types GeoJSON : Point, LineString, Polygon, MultiPolygon, etc.
- Les sites peuvent avoir GPS seul, GeoJSON seul, ou les deux combinés

#### 2. Édition d'un Site

**URL** : `/my/sites/{site}/edit` (lien dynamique selon site)

**Droits d'accès** :
- Si l'utilisateur n'a **pas** la permission de modification :
  - Vue **lecture seule**
  - Alerte jaune expliquant les restrictions
  - Affichage des GPS et photos existantes
  
- Si l'utilisateur **a** la permission de modification :
  - Modification des **coordonnées GPS**
  - Ajout/suppression de **photos**
  - Modification des **données GeoJSON**

## Structure de la Base de Données

### Table `site_user_access`

```sql
- id (primary key)
- user_id (foreign key → users.id)
- site_id (foreign key → sites.id)
- can_edit (boolean) : Permission de modification
- can_collect (boolean) : Permission de collecte de données
- granted_at (timestamp) : Date d'attribution
- granted_by (foreign key → users.id) : Admin qui a donné l'accès
- created_at, updated_at
```

**Index** :
- Unique constraint sur (user_id, site_id)
- Index sur user_id
- Index sur site_id

## Permissions et Contrôles

### Hiérarchie des accès (du plus au moins privilégié)

1. **Super Admin** : Accès total à tous les sites, toujours
2. **Admin Organisation** : Accès à tous les sites de son organisation
3. **Utilisateur avec site assigné** : Accès uniquement aux sites qui lui sont assignés
4. **Utilisateur sans assignment** : Aucun accès aux sites (sauf master list en lecture)

### Méthodes de vérification (Modèle User)

```php
// Vérifier si un utilisateur a accès à un site
$user->hasAccessToSite($site);

// Vérifier si un utilisateur peut éditer un site
$user->canEditSite($site);

// Récupérer les sites assignés
$user->assignedSites;
```

## Cas d'Usage Typiques

### 1. Collecte de données sur le terrain

**Scénario** : Un agent de terrain doit collecter des données GPS et photos sur 5 sites spécifiques.

**Actions** :
1. Super admin va sur `/admin/user-site-access`
2. Trouve l'agent dans la liste
3. Clique sur "Gérer les sites"
4. Assigne les 5 sites avec permissions "Modification" + "Collecte"
5. L'agent accède à `/my/sites` et voit ses 5 sites
6. L'agent modifie GPS et ajoute photos

### 2. Audit temporaire

**Scénario** : Un auditeur externe doit consulter 10 sites sans les modifier.

**Actions** :
1. Super admin crée un compte utilisateur pour l'auditeur
2. Assigne les 10 sites avec **uniquement** permission "Collecte" (pas de modification)
3. L'auditeur voit les sites en lecture seule
4. Après l'audit, l'admin révoque tous les accès

### 3. Mission multi-organisations

**Scénario** : Un coordinateur doit gérer des sites de plusieurs organisations.

**Actions** :
1. Super admin assigne individuellement des sites de différentes organisations
2. Le coordinateur voit tous ces sites dans `/my/sites`
3. Il peut collecter des données sur tous ces sites

## Routes API

### Routes Admin (Super Admin uniquement)
```
GET    /admin/user-site-access                          # Liste des utilisateurs
GET    /admin/user-site-access/{user}/manage            # Gérer les sites d'un utilisateur
POST   /admin/user-site-access/{user}/grant             # Attribuer un site
DELETE /admin/user-site-access/{user}/sites/{site}/revoke  # Retirer un site
POST   /admin/user-site-access/{user}/sites/{site}/update # Modifier permissions
POST   /admin/user-site-access/{user}/bulk-grant        # Attribution en masse
GET    /admin/sites/{site}/users                        # Voir les utilisateurs d'un site
```

### Routes Utilisateur
```
GET    /my/sites                      # Mes sites assignés
GET    /my/sites/{site}/edit          # Éditer un site assigné
PUT    /my/sites/{site}               # Mettre à jour un site
DELETE /my/sites/{site}/delete-photo  # Supprimer une photo
```

## Différences avec `/organisation/sites`

| Critère | `/organisation/sites` | `/my/sites` |
|---------|----------------------|-------------|
| **Attribution** | Par organisation (globale) | Individuelle (utilisateur) |
| **Visibilité** | Tous les membres de l'orga | Uniquement l'utilisateur |
| **Contrôle** | Admin organisation | Super admin |
| **Permissions** | Uniformes pour tous | Granulaires par utilisateur |
| **Cas d'usage** | Gestion organisationnelle | Collecte terrain, missions |

## Messages et Notifications

### Messages de succès
- ✅ "Accès au site accordé avec succès"
- ✅ "Accès au site retiré avec succès"
- ✅ "Permissions mises à jour avec succès"
- ✅ "X site(s) attribué(s) avec succès"
- ✅ "Site mis à jour avec succès"

### Messages d'erreur
- ❌ "L'utilisateur a déjà accès à ce site"
- ❌ "Vous n'avez pas accès à ce site" (403)
- ❌ "Vous n'avez pas la permission de modifier ce site" (403)

### Alertes visuelles
- 🟡 Alerte jaune : "Vous n'avez que les droits de consultation sur ce site"
- 🟢 Tags verts : Permissions accordées (Modification, Collecte)

## Validation et Sécurité

### Contrôles backend
- Vérification que l'utilisateur appartient à une organisation (si applicable)
- Vérification des permissions avant chaque action
- Validation des coordonnées GPS (-90 à 90, -180 à 180)
- Validation du format GeoJSON
- Limitation de taille des photos (5MB)

### Contrôles frontend
- Désactivation des boutons selon permissions
- Masquage des formulaires en lecture seule
- Validation JavaScript du GeoJSON
- Confirmation avant suppression

## Migration et Rollback

### Pour migrer
```bash
php artisan migrate
```

### Pour rollback
```bash
php artisan migrate:rollback
```

Cela supprimera la table `site_user_access` et toutes les attributions individuelles.

## Performances

### Optimisations appliquées
- Index sur les clés étrangères (user_id, site_id)
- Constraint unique pour éviter les doublons
- Eager loading des relations (with())
- Pagination des listes

### Requêtes optimisées
```php
// Charger les sites avec leurs relations
$user->assignedSites()->with(['typeSite', 'commune', 'organisation'])->get();

// Vérifier l'accès sans charger toutes les données
$user->assignedSites()->where('sites.id', $site->id)->exists();
```

## Maintenance

### Nettoyer les accès d'un utilisateur supprimé
Les accès sont automatiquement supprimés grâce à `onDelete('cascade')`.

### Nettoyer les accès d'un site supprimé
Les accès sont automatiquement supprimés grâce à `onDelete('cascade')`.

### Audit des accès
```sql
SELECT u.name, s.nom, sua.granted_at, sua.can_edit, sua.can_collect
FROM site_user_access sua
JOIN users u ON sua.user_id = u.id
JOIN sites s ON sua.site_id = s.id
WHERE granted_at > NOW() - INTERVAL 30 DAY;
```

## Support et Dépannage

> 📖 **Pour des instructions détaillées pas à pas, consultez le [Manuel Utilisateur complet](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md)**

### Problème : L'utilisateur ne voit pas ses sites
1. Vérifier que l'utilisateur est actif (`is_active = true`)
2. Vérifier dans la table `site_user_access` 
3. Vérifier les permissions du middleware

### Problème : Modification refusée
1. Vérifier `can_edit = true` dans `site_user_access`
2. Vérifier que l'utilisateur est authentifié
3. Vérifier les logs Laravel

### Problème : Photos ne s'affichent pas
1. Vérifier que `php artisan storage:link` a été exécuté
2. Vérifier les permissions du dossier `storage/app/public`
3. Vérifier que les chemins dans la base de données sont corrects
