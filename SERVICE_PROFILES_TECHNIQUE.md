# Module de Profils de Services - Documentation Technique

## Vue d'ensemble

Le module de **Profils de Services** permet de collecter, valider et consulter les données sur les services disponibles dans les sites de déplacés. Il couvre 6 secteurs essentiels : Santé, Éducation, WASH, Environnement, Abri/AME, et Gestion/Coordination.

## Architecture

### Base de données

**Table : `service_profiles`**

```sql
- id (bigint, PK)
- site_id (FK → sites)
- date_collecte (date)
- collecteur_id (FK → users)

-- SANTÉ
- sante_disponible (boolean)
- sante_structures_fonctionnelles (integer)
- sante_personnel_medical (integer)
- sante_services_offerts (text, JSON)
- sante_consultations_mois (integer)
- sante_observations (text)

-- ÉDUCATION
- education_disponible (boolean)
- education_ecoles_fonctionnelles (integer)
- education_enseignants (integer)
- education_eleves_inscrits (integer)
- education_salles_classe (integer)
- education_niveaux_offerts (text, JSON)
- education_observations (text)

-- WASH
- wash_disponible (boolean)
- wash_points_eau (integer)
- wash_litres_par_personne (decimal 8,2)
- wash_latrines (integer)
- wash_douches (integer)
- wash_gestion_dechets (boolean)
- wash_observations (text)

-- ENVIRONNEMENT
- environnement_disponible (boolean)
- environnement_gestion_dechets (boolean)
- environnement_drainage (boolean)
- environnement_espaces_verts (boolean)
- environnement_risques (text, JSON)
- environnement_observations (text)

-- ABRI ET AME
- abri_ame_disponible (boolean)
- abri_logements_fonctionnels (integer)
- abri_types (text, JSON)
- abri_menages_ame (integer)
- abri_ame_distribues (text, JSON)
- abri_observations (text)

-- GESTION ET COORDINATION
- gestion_disponible (boolean)
- gestion_comite_site (boolean)
- gestion_membres_comite (integer)
- gestion_mecanisme_plainte (boolean)
- gestion_reunions_mois (integer)
- gestion_partenaires (text, JSON)
- gestion_observations (text)

-- MÉTADONNÉES
- statut (enum: 'brouillon', 'soumis', 'valide', 'rejete')
- notes_generales (text)
- created_at (timestamp)
- updated_at (timestamp)

-- INDEX
INDEX (site_id, date_collecte)
INDEX (statut)
```

### Relations

```php
ServiceProfile
├── belongsTo(Site)
├── belongsTo(User, 'collecteur_id')

Site
└── hasMany(ServiceProfile)

User
└── hasMany(ServiceProfile, 'collecteur_id')
```

## Routes

### Routes principales

```php
GET    /service-profiles              → index    (Liste)
GET    /service-profiles/create       → create   (Formulaire)
POST   /service-profiles              → store    (Enregistrer)
GET    /service-profiles/{id}         → show     (Détails)
GET    /service-profiles/{id}/edit    → edit     (Modifier)
PUT    /service-profiles/{id}         → update   (Mettre à jour)
DELETE /service-profiles/{id}         → destroy  (Supprimer)

-- Actions de workflow
POST   /service-profiles/{id}/submit   → submit   (Soumettre)
POST   /service-profiles/{id}/validate → validate (Valider - super admin)
POST   /service-profiles/{id}/reject   → reject   (Rejeter - super admin)
```

### Middleware appliqué

- Toutes les routes : `auth` (authentification requise)
- `validate`, `reject` : `check.role:super_admin`

## Contrôleur

### ServiceProfileController

**Principales méthodes :**

```php
// Affichage
index()              // Liste des profils (filtrés par permission)
show($id)            // Détails d'un profil
create()             // Formulaire de création
edit($id)            // Formulaire d'édition

// CRUD
store(Request)       // Enregistrer un nouveau profil
update(Request, $id) // Mettre à jour un profil
destroy($id)         // Supprimer un profil

// Workflow
submit($id)          // Soumettre pour validation
validate($id)        // Valider (super admin)
reject(Request, $id) // Rejeter avec raison (super admin)

// Permissions privées
getAccessibleSites()           // Sites accessibles par l'utilisateur
userCanAccessSite($siteId)     // Vérifier l'accès à un site
userCanViewProfile($profile)   // Vérifier le droit de lecture
userCanEditProfile($profile)   // Vérifier le droit d'édition
```

### Logique de permissions

**Accès aux sites :**
```php
Super Admin : Tous les sites
Utilisateur : 
  - Sites de son organisation
  - Sites assignés individuellement avec can_collect=true
```

**Modification d'un profil :**
```php
Autorisé si :
  - Super Admin (pour tout)
  - Collecteur du profil ET statut='brouillon'
```

**Suppression d'un profil :**
```php
Même règle que modification
```

## Modèle

### ServiceProfile

**Casts automatiques :**
```php
'date_collecte' => 'date',
'sante_disponible' => 'boolean',
// ... tous les booléens

// Champs JSON
'sante_services_offerts' => 'array',
'education_niveaux_offerts' => 'array',
'environnement_risques' => 'array',
'abri_types' => 'array',
'abri_ame_distribues' => 'array',
'gestion_partenaires' => 'array',
```

**Méthodes utilitaires :**

```php
hasAnyService()               // Vérifie si au moins 1 service disponible
getAvailableServicesCount()   // Compte les secteurs avec services
getAvailableServices()        // Liste des secteurs disponibles (array)
getStatusBadgeClass()         // Classe CSS pour le badge de statut
getStatusLabel()              // Label formaté du statut
```

**Scopes :**
```php
forSite($siteId)      // Filtrer par site
byStatus($status)     // Filtrer par statut
recent($days = 30)    // Collectes récentes
```

## Vues Blade

### Structure des fichiers

```
resources/views/service-profiles/
├── index.blade.php         # Liste des profils
├── create.blade.php        # Formulaire de création
├── edit.blade.php          # Formulaire d'édition (extends create)
└── show.blade.php          # Détails d'un profil
```

### Composants JavaScript

**Fonction de toggle des sections :**
```javascript
function toggleSection(sectionName) {
    const checkbox = document.getElementById(sectionName + '_disponible');
    const section = document.getElementById(sectionName + '-section');
    
    if (checkbox.checked) {
        section.style.display = /* 'grid' ou 'block' selon secteur */;
    } else {
        section.style.display = 'none';
    }
}
```

**Événements :**
- `onchange` sur chaque checkbox de secteur
- Affiche/masque dynamiquement les champs du secteur

### Layout et design

**Couleurs par secteur :**
- Santé : Rouge (`red-500`)
- Éducation : Bleu (`blue-500`)
- WASH : Cyan (`cyan-500`)
- Environnement : Vert (`green-500`)
- Abri/AME : Jaune (`yellow-500`)
- Gestion : Violet (`purple-500`)

**Badges de statut :**
```php
'brouillon' => 'bg-gray-200 text-gray-800'
'soumis'    => 'bg-blue-200 text-blue-800'
'valide'    => 'bg-green-200 text-green-800'
'rejete'    => 'bg-red-200 text-red-800'
```

## Workflow de validation

### Diagramme d'états

```
┌─────────────┐
│  BROUILLON  │
└──────┬──────┘
       │ submit()
       ↓
┌─────────────┐
│   SOUMIS    │
└──────┬──────┘
       │
       ├─→ validate() → VALIDÉ
       │
       └─→ reject()   → REJETÉ
```

### Transitions autorisées

| Statut actuel | Action | Nouveau statut | Qui peut ? |
|---------------|--------|----------------|------------|
| brouillon | submit | soumis | Collecteur |
| soumis | validate | valide | Super Admin |
| soumis | reject | rejete | Super Admin |

**Règles importantes :**
- Un profil **soumis** ne peut plus être modifié
- Un profil **validé** est en lecture seule définitivement
- Un profil **rejeté** ne peut pas être corrigé (créer un nouveau)

## Validation des données

### Règles de validation (store/update)

```php
[
    'site_id' => 'required|exists:sites,id',
    'date_collecte' => 'required|date',
    
    // Tous les booléens
    '*_disponible' => 'boolean',
    
    // Tous les nombres
    '*_structures_fonctionnelles' => 'nullable|integer|min:0',
    '*_personnel_medical' => 'nullable|integer|min:0',
    // ... etc
    
    // Décimaux
    'wash_litres_par_personne' => 'nullable|numeric|min:0',
    
    // Arrays (JSON)
    'sante_services_offerts' => 'nullable|array',
    'education_niveaux_offerts' => 'nullable|array',
    // ... etc
    
    // Textes
    '*_observations' => 'nullable|string',
    'notes_generales' => 'nullable|string',
]
```

### Validation côté frontend

- Champs obligatoires : `site_id`, `date_collecte`
- Min="0" sur tous les champs numériques
- Step="0.1" sur les décimaux

## Intégration avec le système d'accès

### Vérification de l'accès au site

```php
private function userCanAccessSite($siteId)
{
    $user = Auth::user();
    
    // Super admin : accès total
    if ($user->role === 'super_admin') {
        return true;
    }
    
    $site = Site::find($siteId);
    
    // Organisation du site
    if ($site->organisation_id === $user->organisation_id) {
        return true;
    }
    
    // Accès individuel avec permission de collecte
    return $site->userAccess()
        ->where('user_id', $user->id)
        ->where('can_collect', true)
        ->exists();
}
```

## Cas d'usage

### 1. Collecteur terrain

**Scénario :**
Un agent terrain arrive sur un site pour documenter les services.

**Actions :**
1. Se connecte à l'app
2. Va dans "Profils de Services"
3. Clique "Nouvelle collecte"
4. Sélectionne le site
5. Coche les secteurs disponibles
6. Remplit les champs pour chaque secteur
7. Enregistre (brouillon) ou soumet directement

**Résultat :**
Profil créé en brouillon ou soumis pour validation.

### 2. Super Admin - Validation

**Scénario :**
Le super admin reçoit un profil soumis à valider.

**Actions :**
1. Va dans "Profils de Services"
2. Filtre par "Soumis"
3. Ouvre le profil à valider
4. Vérifie la cohérence des données
5. Option A : Clique "Valider" → Statut = validé
6. Option B : Clique "Rejeter", saisit raison → Statut = rejeté

**Résultat :**
Profil validé ou rejeté avec feedback au collecteur.

### 3. Consultation historique

**Scénario :**
Un coordinateur veut voir l'évolution des services sur un site.

**Actions :**
1. Va dans "Profils de Services"
2. Recherche le nom du site
3. Voit tous les profils du site triés par date
4. Compare les indicateurs entre les collectes

**Résultat :**
Vision temporelle de l'évolution des services.

## Améliorations futures possibles

### Court terme
- [ ] Export Excel des profils
- [ ] Filtres avancés (date, secteur spécifique)
- [ ] Graphiques d'évolution temporelle
- [ ] Notifications email lors de la soumission

### Moyen terme
- [ ] Tableau de bord avec indicateurs agrégés
- [ ] Comparaison multi-sites
- [ ] Révision d'un profil rejeté (au lieu de recréer)
- [ ] Historique des modifications

### Long terme
- [ ] API REST pour applications mobiles
- [ ] Collecte hors ligne (PWA)
- [ ] Validation en plusieurs étapes (coordinateur → admin)
- [ ] Intégration avec systèmes externes (DHIS2, etc.)

## Tests

### Tests unitaires recommandés

```php
// Modèle
- hasAnyService() retourne true si au moins 1 secteur actif
- getAvailableServicesCount() compte correctement
- Scopes filtrent correctement

// Contrôleur
- Utilisateur ne peut pas voir le profil d'un autre si non autorisé
- Super admin peut tout voir
- Seul le collecteur peut modifier son brouillon
- Transitions de statut respectent les règles

// Permissions
- userCanAccessSite() respecte organisation + attribution
- userCanEditProfile() vérifie correctement
```

### Tests de fonctionnalité

```php
// Workflow complet
- Créer un profil → brouillon
- Soumettre → soumis (plus modifiable)
- Valider → validé (lecture seule)
- Rejeter → rejeté (avec raison)

// Permissions
- Utilisateur sans accès → 403
- Modification d'un profil soumis → interdit
```

## Dépannage

### Problème : "Vous n'avez pas accès à ce site"

**Causes possibles :**
- L'utilisateur n'appartient pas à l'organisation du site
- L'utilisateur n'a pas d'attribution individuelle
- L'attribution existe mais `can_collect = false`

**Solution :**
Vérifier dans `site_user_access` ou demander au super admin.

### Problème : Impossible de modifier un profil

**Causes possibles :**
- Le profil n'est pas en statut "brouillon"
- L'utilisateur n'est pas le collecteur
- L'utilisateur n'est pas super admin

**Solution :**
Seuls les brouillons créés par soi-même sont modifiables.

### Problème : Les champs JSON ne s'affichent pas

**Causes possibles :**
- Cast manquant dans le modèle
- Données invalides en base

**Solution :**
Vérifier que le cast `'array'` est bien défini.

---

## Contribution

Pour contribuer à ce module :

1. Respecter les conventions Laravel PSR-12
2. Ajouter des commentaires pour les méthodes complexes
3. Tester les permissions avant de soumettre
4. Documenter les nouvelles fonctionnalités

---

**Auteur** : DMS CCCM Development Team  
**Date** : Mars 2026  
**Version** : 1.0.0
