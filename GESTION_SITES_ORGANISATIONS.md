# Gestion des Sites par Organisation

> 📋 **[Retour à l'index de la documentation](DOCUMENTATION.md)** | Consultez tous les guides disponibles

## Vue d'ensemble

Ce système permet aux organisations de gérer leurs sites de manière autonome. Les administrateurs système peuvent attribuer des sites aux organisations, et les membres des organisations peuvent ensuite modifier les informations de leurs sites.

## Fonctionnalités

### Pour les Super Administrateurs

#### Attribution de Sites aux Organisations

**URL** : `/admin/sites`

**Fonctionnalités disponibles** :
- Voir tous les sites avec leur statut d'attribution
- Filtrer les sites par :
  - Organisation
  - Statut (attribué/non attribué)
  - Nom du site (recherche)
- Attribuer un site à une organisation
- Retirer un site d'une organisation
- Attribution en masse de plusieurs sites à une organisation

**Comment attribuer un site** :
1. Accéder à la page de gestion des sites
2. Pour un site non attribué, cliquer sur le bouton "+"
3. Sélectionner l'organisation dans la liste déroulante
4. Valider l'attribution

**Attribution en masse** :
1. Cocher les cases des sites à attribuer
2. Sélectionner l'organisation de destination
3. Cliquer sur "Attribuer" avec le nombre de sites sélectionnés

### Pour les Organisations

#### Consultation des Sites

**URL** : `/organisation/sites`

Les organisations peuvent voir tous les sites qui leur ont été attribués sous forme de cartes avec :
- Photo principale du site (si disponible)
- Badge GPS si les coordonnées sont enregistrées
- Nom et code du site
- Localisation (territoire, province)
- Type de site
- Population (ménages et individus)
- Nombre de photos

#### Gestion d'un Site

**URL** : `/organisation/sites/{id}/edit`

Pour chaque site attribué, les organisations peuvent :

##### 1. Consulter les Informations Générales (lecture seule)
- Code du site
- Type de site
- Catégorie
- Zone de santé
- Population
- Date de mise à jour

##### 2. Modifier les Coordonnées GPS
- Ajouter/modifier la latitude (entre -90 et 90)
- Ajouter/modifier la longitude (entre -180 et 180)
- Un indicateur visuel confirme l'enregistrement des coordonnées

**Exemple de coordonnées** :
```
Latitude : -4.3250623
Longitude : 15.3350623
```

##### 3. Gérer les Données GeoJSON
- Ajouter/modifier des données GeoJSON
- Format JSON standard
- Validation automatique du format JSON avant enregistrement

**Exemple de GeoJSON** :
```json
{
  "type": "Point",
  "coordinates": [15.3350623, -4.3250623]
}
```

**Exemple de Polygon GeoJSON** :
```json
{
  "type": "Polygon",
  "coordinates": [
    [
      [15.33, -4.32],
      [15.34, -4.32],
      [15.34, -4.33],
      [15.33, -4.33],
      [15.33, -4.32]
    ]
  ]
}
```

##### 4. Gérer les Photos du Site
- Voir toutes les photos du site dans une galerie
- Ajouter de nouvelles photos (jusqu'à 5MB par image)
- Upload multiple de plusieurs photos à la fois
- Supprimer des photos existantes (au survol de la photo)
- Formats supportés : JPG, PNG, GIF

## Structure de la Base de Données

### Modifications apportées à la table `sites`

Nouvelles colonnes ajoutées :
- `organisation_id` (nullable) : Référence à l'organisation qui gère le site
- `photos` (JSON) : Tableau des chemins vers les photos du site
- `geojson_data` (JSON) : Données géospatiales au format GeoJSON

## Permissions et Sécurité

### Contrôles d'accès

- **Super Admin** :
  - Peut attribuer/retirer n'importe quel site à/de n'importe quelle organisation
  - Accès à tous les sites

- **Utilisateurs d'organisation** :
  - Peuvent uniquement voir et modifier les sites attribués à leur organisation
  - Ne peuvent pas accéder aux sites d'autres organisations
  - Ne peuvent pas modifier les informations administratives (nom, type, etc.)

### Vérifications de sécurité

Chaque action de modification vérifie :
1. Que l'utilisateur appartient à une organisation
2. Que le site appartient bien à l'organisation de l'utilisateur
3. Les droits d'accès basés sur le rôle de l'utilisateur

## Stockage des Photos

Les photos sont stockées dans :
```
storage/app/public/sites/photos/
```

Et accessibles publiquement via :
```
public/storage/sites/photos/
```

Le lien symbolique doit être créé avec :
```bash
php artisan storage:link
```

## Routes API

### Routes Admin
```
GET    /admin/sites                                  # Liste des sites
POST   /admin/sites/{site}/assign-to-organisation    # Attribuer un site
DELETE /admin/sites/{site}/remove-from-organisation  # Retirer un site
POST   /admin/sites/bulk-assign                      # Attribution en masse
```

### Routes Organisation
```
GET    /organisation/sites                    # Liste des sites de l'organisation
GET    /organisation/sites/{site}/edit        # Éditer un site
PUT    /organisation/sites/{site}             # Mettre à jour un site
DELETE /organisation/sites/{site}/delete-photo # Supprimer une photo
```

## Validation des Données

### Coordonnées GPS
- Latitude : nombre décimal entre -90 et 90
- Longitude : nombre décimal entre -180 et 180

### Photos
- Taille maximale : 5MB par image
- Formats acceptés : JPEG, PNG, GIF
- Upload multiple supporté

### GeoJSON
- Doit être un JSON valide
- Validation côté client et serveur

## Messages d'Erreur

Le système affiche des messages clairs pour :
- Succès des opérations (fond vert)
- Erreurs de validation (fond rouge)
- Permissions refusées (erreur 403)

## Utilisation Recommandée

1. **Administrateur** : Attribuer les sites aux organisations en fonction de leur zone géographique ou de responsabilité
2. **Organisation** : 
   - Compléter les coordonnées GPS lors de visites terrain
   - Ajouter des photos pour documenter l'état du site
   - Mettre à jour les données GeoJSON si des polygones précis sont disponibles

## Support Technique

En cas de problème :
- Vérifier que l'utilisateur appartient bien à une organisation
- Vérifier que le site a été attribué à l'organisation
- Vérifier les permissions de fichiers pour le stockage des photos
- Consulter les logs Laravel pour les erreurs détaillées
