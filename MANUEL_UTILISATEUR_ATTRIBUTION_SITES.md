# Manuel Utilisateur - Système d'Attribution de Sites

> 📋 **[Retour à l'index de la documentation](DOCUMENTATION.md)** | Consultez tous les guides et tutoriels disponibles

## 📖 Table des matières

1. [Introduction](#introduction)
2. [Accès au système](#accès-au-système)
3. [Guide Super Administrateur](#guide-super-administrateur)
4. [Guide Utilisateur](#guide-utilisateur)
5. [FAQ - Questions fréquentes](#faq---questions-fréquentes)
6. [Dépannage](#dépannage)
7. [Bonnes pratiques](#bonnes-pratiques)

---

## Introduction

### Qu'est-ce que le système d'attribution de sites ?

Le système d'attribution de sites permet au **Super Administrateur** d'accorder à des utilisateurs spécifiques l'accès à certains sites pour la collecte de données sur le terrain. Cette fonctionnalité complète le système d'accès par organisation en offrant une gestion plus granulaire.

### Les 3 niveaux d'accès aux sites

| Niveau d'accès | Pour qui ? | Comment ? | Cas d'usage |
|----------------|------------|-----------|-------------|
| **Organisation** | Tous les membres d'une organisation | Automatique via l'organisation | Gestion globale institutionnelle |
| **Individuel** | Utilisateurs sélectionnés | Attribution par le super admin | Collecte terrain, missions temporaires |
| **Master List** | Tous les utilisateurs | Lecture seule | Consultation, exports, statistiques |

### Les deux types de permissions

Quand un site est attribué à un utilisateur, deux permissions peuvent être accordées :

- **🔧 Modification** : L'utilisateur peut modifier les coordonnées GPS, ajouter/supprimer des photos, modifier les données GeoJSON
- **📊 Collecte** : L'utilisateur peut collecter et enregistrer des données sur le site

> **Note** : Un utilisateur peut avoir uniquement la permission de collecte (lecture seule sur les données géographiques) ou les deux permissions.

---

## Accès au système

### Connexion

1. Ouvrez votre navigateur web
2. Accédez à l'URL : `http://127.0.0.1:8000` (ou l'adresse fournie par votre administrateur)
3. Entrez votre email et mot de passe
4. Cliquez sur "Connexion"

### Navigation dans le menu

Le menu latéral gauche contient plusieurs liens. Pour le système d'attribution de sites :

- **Super Admin** : "Attribution des sites" → Gérer les accès utilisateurs
- **Utilisateurs** : "Mes Sites" → Accéder à vos sites assignés
- **Tous** : "Master list" → Consulter tous les sites (lecture seule)

---

## Guide Super Administrateur

### 📋 Vue d'ensemble des utilisateurs

#### Accéder à la page de gestion

1. Dans le menu latéral, cliquez sur **"Attribution des sites"**
2. Vous arrivez sur `/admin/user-site-access`

#### Comprendre la page

La page affiche un tableau avec :
- **Nom de l'utilisateur**
- **Email**
- **Organisation** (si applicable)
- **Rôle** (utilisateur / admin organisation)
- **Nombre de sites assignés**
- **Bouton "Gérer les sites"**

#### Utiliser les filtres

En haut de la page, vous pouvez filtrer les utilisateurs :

**1. Recherche par nom ou email**
```
┌─────────────────────────────────┐
│ 🔍 Rechercher...                │
└─────────────────────────────────┘
```
- Tapez quelques lettres du nom ou de l'email
- Les résultats se filtrent automatiquement

**2. Filtre par organisation**
```
┌─────────────────────────────────┐
│ Sélectionner une organisation   │
└─────────────────────────────────┘
```
- Sélectionnez une organisation pour voir uniquement ses membres

**3. Filtre par rôle**
```
┌─────────────────────────────────┐
│ Tous les rôles                   │
│ ◆ Utilisateur                    │
│ ◆ Admin organisation             │
└─────────────────────────────────┘
```
- Filtrez par type d'utilisateur

#### Actions disponibles

- **Bouton "Gérer les sites"** : Ouvre la page de gestion détaillée pour cet utilisateur
- **Pagination** : Naviguez entre les pages si vous avez beaucoup d'utilisateurs

---

### 🎯 Gestion des sites d'un utilisateur

#### Accéder à la page

1. Depuis la vue d'ensemble, cliquez sur **"Gérer les sites"** pour un utilisateur
2. Vous arrivez sur `/admin/user-site-access/{id}/manage`

#### Structure de la page

La page est divisée en **deux colonnes** :

**Colonne gauche : Sites assignés**
- Liste des sites déjà attribués à l'utilisateur
- Permissions actives (Modification / Collecte)
- Date d'attribution
- Bouton pour retirer l'accès

**Colonne droite : Attribuer de nouveaux sites**
- Barre de recherche et filtres
- Formulaire d'attribution unique
- Formulaire d'attribution en masse

---

### 📍 Rechercher et filtrer les sites disponibles

Avant d'attribuer des sites, utilisez les outils de recherche pour trouver rapidement ce que vous cherchez.

#### Barre de recherche

```
┌────────────────────────────────────────────┐
│ 🔍 Rechercher un site par nom...           │
└────────────────────────────────────────────┘
```

**Comment l'utiliser :**
1. Tapez le nom du site (ou une partie)
2. Les résultats se filtrent instantanément
3. Exemple : Tapez "école" pour trouver tous les sites contenant ce mot

#### Filtres géographiques

La deuxième ligne contient 3 menus déroulants :

```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│Province ▼    │ │Territoire ▼  │ │Commune ▼     │
└──────────────┘ └──────────────┘ └──────────────┘
```

**Comment les utiliser :**

**Filtre 1 : Province**
- Affiche toutes les provinces où il y a des sites
- Sélectionnez une province pour ne voir que ses sites
- Exemple : Sélectionnez "Nord-Kivu"

**Filtre 2 : Territoire**
- Liste tous les territoires disponibles
- Combine avec le filtre province si vous le souhaitez
- Exemple : Sélectionnez "Masisi"

**Filtre 3 : Commune**
- Liste toutes les communes (zones de santé)
- Affine encore plus votre recherche
- Exemple : Sélectionnez "Kirotshe"

#### Réinitialiser les filtres

Cliquez sur **"Réinitialiser les filtres"** pour effacer tous les critères de recherche.

#### Compteur de sites disponibles

En haut à droite, vous voyez :
```
X disponible(s)
```
Ce nombre indique combien de sites correspondent à vos filtres et ne sont pas encore assignés.

---

### ➕ Attribuer un site unique

Utilisez cette méthode pour attribuer **un site à la fois**.

#### Étapes détaillées

**Étape 1 : Sélectionner le site**

```
┌────────────────────────────────────────────┐
│ Sélectionner un site                       │
│ ═══════════════════════════════════════    │
│ Choisir un site...                    ▼    │
│ ─────────────────────────────────────────  │
│ Site A - Masisi, Nord-Kivu (ONG ABC)       │
│ Site B - Rutshuru, Nord-Kivu (ONG XYZ)     │
│ Site C - Goma, Nord-Kivu (ONG ABC)         │
└────────────────────────────────────────────┘
```

1. Cliquez sur le menu déroulant
2. Parcourez la liste ou utilisez les filtres au-dessus
3. Cliquez sur le site désiré

> **Info** : Entre parenthèses, vous voyez l'organisation qui gère le site

**Étape 2 : Définir les permissions**

```
☑ Autoriser la modification
   L'utilisateur peut modifier GPS, photos, GeoJSON

☑ Autoriser la collecte de données
   L'utilisateur peut collecter des données
```

- Cochez les permissions souhaitées
- Par défaut, les deux sont cochées
- Vous pouvez décocher "Modification" pour donner un accès en lecture seule

**Étape 3 : Attribuer**

```
┌────────────────────────────┐
│   Attribuer le site        │
└────────────────────────────┘
```

Cliquez sur le bouton bleu "Attribuer le site"

**Résultat :**
- Message de confirmation vert : ✅ "Accès au site accordé avec succès"
- Le site apparaît dans la colonne gauche "Sites assignés"

---

### 📦 Attribution en masse

Utilisez cette méthode pour attribuer **plusieurs sites en une seule fois**.

#### Étapes détaillées

**Étape 1 : Sélectionner les sites**

Dans la section "Attribution en masse", vous voyez une liste de cases à cocher :

```
┌────────────────────────────────────────────┐
│ ☐ Site A - Masisi • ONG ABC                │
│ ☐ Site B - Rutshuru • ONG XYZ              │
│ ☑ Site C - Goma • ONG ABC                  │
│ ☑ Site D - Beni • ONG ABC                  │
│ ☐ Site E - Butembo • ONG XYZ               │
└────────────────────────────────────────────┘
```

1. Cochez tous les sites que vous voulez attribuer
2. Vous pouvez utiliser les filtres pour afficher uniquement certains sites
3. Cochez uniquement ceux que vous voulez attribuer

> **Astuce** : Utilisez les filtres (province, territoire, commune) pour afficher uniquement les sites d'une région, puis cochez-les tous d'un coup.

**Étape 2 : Définir les permissions globales**

```
☑ Autoriser la modification
☑ Autoriser la collecte
```

Ces permissions s'appliqueront à **tous** les sites sélectionnés.

**Étape 3 : Attribuer en masse**

Le bouton indique le nombre de sites sélectionnés :

```
┌────────────────────────────┐
│   Attribuer (3) sites      │
└────────────────────────────┘
```

> **Note** : Le bouton est désactivé (grisé) tant que vous n'avez coché aucun site.

Cliquez sur le bouton vert

**Résultat :**
- Message de confirmation : ✅ "3 site(s) attribué(s) avec succès"
- Tous les sites apparaissent dans "Sites assignés"

---

### ⚙️ Gérer les permissions en temps réel

Une fois qu'un site est attribué, vous pouvez modifier les permissions sans retirer l'accès.

#### Dans la colonne "Sites assignés"

Chaque site assigné affiche deux cases à cocher :

```
┌────────────────────────────────────────────┐
│ 📍 Site de référence - Masisi             │
│    Masisi, Nord-Kivu                       │
│                                            │
│ ☑ Modification    ☑ Collecte              │
│                                            │
│ Accordé le 28/03/2026 10:30                │
└────────────────────────────────────────────┘
```

#### Modifier une permission

1. Cliquez simplement sur la case à cocher
2. La modification est **instantanée** (pas besoin de bouton "Enregistrer")
3. La permission est mise à jour immédiatement dans la base de données

**Exemples d'utilisation :**

- **Passer en lecture seule** : Décochez "Modification", laissez "Collecte"
- **Accorder la modification** : Cochez "Modification"
- **Retirer complètement** : Utilisez le bouton poubelle (voir ci-dessous)

---

### 🗑️ Retirer l'accès à un site

#### Méthode

Dans la colonne "Sites assignés", chaque site a une icône poubelle rouge en haut à droite :

```
┌────────────────────────────────────────────┐
│ 📍 Site de référence              🗑️       │
│    Masisi, Nord-Kivu                       │
└────────────────────────────────────────────┘
```

#### Étapes

1. Cliquez sur l'icône poubelle 🗑️
2. Une confirmation apparaît : "Retirer l'accès à ce site ?"
3. Cliquez sur "OK" pour confirmer

**Résultat :**
- Message : ✅ "Accès au site retiré avec succès"
- Le site disparaît de la liste des sites assignés
- L'utilisateur ne peut plus y accéder

> **Important** : Cette action est immédiate. L'utilisateur perd l'accès instantanément.

---

### 📊 Informations affichées pour chaque site assigné

Pour chaque site dans la colonne gauche, vous voyez :

```
┌────────────────────────────────────────────┐
│ 📍 Nom du site                    🗑️       │
│    Territoire, Province                    │
│                                            │
│ ☑ Modification    ☑ Collecte              │
│                                            │
│ Accordé le 28/03/2026 10:30                │
└────────────────────────────────────────────┘
```

**Détails :**
- **Nom du site** : Nom complet du site
- **Localisation** : Territoire et province
- **Permissions** : Cases à cocher modifiables
- **Date d'attribution** : Quand l'accès a été accordé

---

### ⚡ Cas d'usage pratiques

#### Cas 1 : Agent de terrain pour une mission

**Contexte :** Un agent doit collecter des données GPS et photos sur 5 sites dans la région de Masisi.

**Actions :**
1. Allez sur "Attribution des sites"
2. Trouvez l'agent dans la liste
3. Cliquez sur "Gérer les sites"
4. Utilisez les filtres : Province = "Nord-Kivu", Territoire = "Masisi"
5. Cochez les 5 sites dans "Attribution en masse"
6. Assurez-vous que "Modification" et "Collecte" sont cochées
7. Cliquez sur "Attribuer (5) sites"

**Résultat :** L'agent peut maintenant accéder aux 5 sites depuis "Mes Sites" et modifier les données.

---

#### Cas 2 : Audit temporaire

**Contexte :** Un auditeur externe doit consulter 10 sites sans pouvoir les modifier, pour une durée de 2 semaines.

**Actions lors de l'attribution :**
1. Trouvez l'auditeur
2. Cliquez sur "Gérer les sites"
3. Sélectionnez les 10 sites en masse
4. ⚠️ **Décochez "Autoriser la modification"**
5. Laissez "Autoriser la collecte" cochée
6. Attribuez les sites

**Actions après l'audit (2 semaines plus tard) :**
1. Retournez sur la page de gestion de l'auditeur
2. Cliquez sur la poubelle 🗑️ pour chaque site (ou retirez tous les accès)

**Résultat :** L'auditeur peut consulter les données mais pas les modifier. Après l'audit, il n'a plus accès.

---

#### Cas 3 : Coordinateur multi-organisations

**Contexte :** Un coordinateur doit superviser des sites de 3 organisations différentes.

**Actions :**
1. Trouvez le coordinateur
2. Sur la page de gestion, ne filtrez PAS par organisation
3. Recherchez et attribuez les sites des 3 organisations
4. Accordez les deux permissions (Modification + Collecte)

**Résultat :** Le coordinateur voit tous ses sites dans "Mes Sites", peu importe l'organisation propriétaire.

---

#### Cas 4 : Modification de permissions après attribution

**Contexte :** Un utilisateur avait les droits de modification, mais vous voulez le passer en lecture seule.

**Actions :**
1. Allez sur la page de gestion de l'utilisateur
2. Dans "Sites assignés", trouvez le site
3. Décochez simplement "Modification"
4. Laissez "Collecte" cochée

**Résultat :** Changement instantané. L'utilisateur voit maintenant le site en lecture seule.

---

## Guide Utilisateur

### 🗺️ Accéder à mes sites

#### Navigation

1. Connectez-vous à l'application
2. Dans le menu latéral gauche, cliquez sur **"Mes Sites"**
3. Vous arrivez sur `/my/sites`

---

### 📍 Comprendre la page "Mes Sites"

#### Structure de la page

La page affiche :

**1. Barre de recherche**
```
┌────────────────────────────────────────────┐
│ 🔍 Rechercher un site...                   │
└────────────────────────────────────────────┘
```
- Tapez pour filtrer vos sites par nom

**2. Carte interactive**
```
┌────────────────────────────────────────────┐
│                                            │
│         🗺️ CARTE INTERACTIVE              │
│                                            │
│     📍 📍     📍                          │
│            📍      📍                      │
│                                            │
└────────────────────────────────────────────┘
```
- Affiche tous vos sites avec coordonnées GPS
- Cliquez sur un marqueur pour voir les détails
- Bouton "Gérer le site" dans le popup

**3. Liste des sites en cartes**

Chaque site est affiché dans une carte visuelle :

```
┌────────────────────────────────────────────┐
│ 🖼️ [Photo ou icône placeholder]           │
│                            GPS ✓ Assigné   │
│                                            │
│ 📍 Nom du site                    ABC-001  │
│                                            │
│ 📍 Masisi, Nord-Kivu                       │
│ 🏢 Type : Camp de déplacés                 │
│ 👥 5,200 personnes (1,040 ménages)         │
│ 🏛️ ONG Example                             │
│                                            │
│ ✓ Modification  ✓ Collecte                │
│                                            │
│ 🖼️ 3                         [Gérer]       │
└────────────────────────────────────────────┘
```

---

### 🏷️ Comprendre les badges et tags

#### Badges en haut à droite de la photo

**Badge GPS vert** : `GPS`
- Le site a des coordonnées GPS enregistrées
- Il apparaît sur la carte

**Badge bleu** : `Assigné`
- Ce site vous a été attribué individuellement
- (vs les sites de votre organisation)

#### Tags de permissions

En bas de chaque carte :

**Tag vert** : `✓ Modification`
- Vous pouvez modifier GPS, photos, GeoJSON
- Vous pouvez ajouter/supprimer des photos

**Tag bleu** : `✓ Collecte`
- Vous pouvez collecter des données
- Vous avez accès à toutes les informations

> Si vous n'avez pas la permission "Modification", vous verrez une alerte jaune sur la page d'édition.

---

### ✏️ Modifier un site

#### Accéder à la page de modification

**Méthode 1 : Depuis la carte**
1. Cliquez sur le bouton **"Gérer"** en bas de la carte du site

**Méthode 2 : Depuis la carte interactive**
1. Cliquez sur un marqueur 📍
2. Dans le popup, cliquez sur **"Gérer le site"**

Vous arrivez sur `/my/sites/{id}/edit`

---

### 📝 Page d'édition d'un site

#### Mode lecture seule (sans permission "Modification")

Si vous n'avez pas la permission de modification, vous voyez :

```
┌────────────────────────────────────────────┐
│ ⚠️ LECTURE SEULE                           │
│ Vous n'avez que les droits de consultation │
│ sur ce site.                               │
└────────────────────────────────────────────┘
```

**Ce que vous pouvez faire :**
- ✅ Voir toutes les informations
- ✅ Voir les photos
- ✅ Voir les coordonnées GPS
- ✅ Voir les données GeoJSON
- ❌ Pas de modifications possibles

---

#### Mode édition complète (avec permission "Modification")

Si vous avez la permission, la page affiche des formulaires modifiables.

**Structure de la page :**

```
┌────────────────────────────────────────────┐
│ Informations générales (lecture seule)     │
│ - Nom du site                              │
│ - Code du site                             │
│ - Type, catégorie                          │
│ - Localisation                             │
│ - Population                               │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│ 📍 Coordonnées GPS (modifiable)            │
│                                            │
│ Latitude  : [ -1.234567 ]                  │
│ Longitude : [ 29.123456 ]                  │
│                                            │
│ 🗺️ [Carte de prévisualisation]            │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│ 📷 Photos (modifiable)                     │
│                                            │
│ [Photo 1] 🗑️  [Photo 2] 🗑️  [Photo 3] 🗑️  │
│                                            │
│ [Choisir des fichiers] [Ajouter]          │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│ 🗺️ Données GeoJSON (modifiable)           │
│                                            │
│ {                                          │
│   "type": "Feature",                       │
│   "geometry": {                            │
│     "type": "Polygon",                     │
│     "coordinates": [...]                   │
│   }                                        │
│ }                                          │
│                                            │
│ ✓ JSON valide                              │
└────────────────────────────────────────────┘

       [Annuler]  [Enregistrer les modifications]
```

---

### 📍 Modifier les coordonnées GPS

#### Étapes

1. **Modifier la latitude**
   - Cliquez dans le champ "Latitude"
   - Entrez une valeur entre -90 et 90
   - Format : nombre décimal (ex: -1.234567)

2. **Modifier la longitude**
   - Cliquez dans le champ "Longitude"
   - Entrez une valeur entre -180 et 180
   - Format : nombre décimal (ex: 29.123456)

3. **Vérifier sur la carte**
   - Une carte de prévisualisation s'affiche automatiquement
   - Le marqueur se place aux coordonnées saisies
   - Vérifiez visuellement que c'est correct

4. **Enregistrer**
   - Descendez en bas de la page
   - Cliquez sur "Enregistrer les modifications"

**Validation :**
- Si les coordonnées sont incorrectes, un message d'erreur s'affiche
- Les valeurs doivent être dans les limites géographiques
- Format requis : nombre décimal avec point (pas de virgule)

---

### 📷 Gérer les photos

#### Voir les photos existantes

Les photos sont affichées en vignettes :
```
[🖼️ Photo 1] [🖼️ Photo 2] [🖼️ Photo 3]
```

Cliquez sur une photo pour la voir en grand (dans certains navigateurs).

#### Supprimer une photo

1. Cliquez sur l'icône poubelle 🗑️ en haut à droite de la photo
2. Confirmez la suppression
3. La photo est immédiatement supprimée

> **Attention** : La suppression est définitive et immédiate.

#### Ajouter de nouvelles photos

1. Cliquez sur **"Choisir des fichiers"** ou faites glisser des fichiers
2. Sélectionnez une ou plusieurs photos depuis votre ordinateur
3. Formats acceptés : JPG, PNG, GIF, WebP
4. Taille maximum par photo : **5 MB**
5. Cliquez sur **"Ajouter"** ou le bouton similaire
6. Les photos sont téléchargées et ajoutées

**Conseils :**
- Prenez des photos claires et bien cadrées
- Privilégiez les photos en mode paysage pour les sites
- Compressez les photos si elles dépassent 5 MB

---

### 🗺️ Modifier les données GeoJSON

#### Qu'est-ce que le GeoJSON ?

Le GeoJSON est un format de données géographiques qui permet de définir :
- Des **points** (location précise)
- Des **lignes** (routes, rivières)
- Des **polygones** (zones, frontières de site)

#### Modifier le GeoJSON

1. Localisez la section "Données GeoJSON"
2. Un éditeur de texte affiche le JSON actuel
3. Modifiez le contenu
4. Une validation en temps réel vérifie que le JSON est correct

**Indicateur de validation :**
- ✓ JSON valide (vert) : Vous pouvez enregistrer
- ✗ JSON invalide (rouge) : Corrigez les erreurs avant d'enregistrer

#### Exemple de GeoJSON valide

**Exemple simple avec un point :**

```json
{
  "type": "Feature",
  "geometry": {
    "type": "Point",
    "coordinates": [29.123456, -1.234567]
  },
  "properties": {
    "nom": "Zone Centre",
    "description": "Point central du site"
  }
}
```

**Exemple avec un polygone (zone délimitée) :**

```json
{
  "type": "Feature",
  "geometry": {
    "type": "Polygon",
    "coordinates": [[
      [29.123, -1.234],
      [29.125, -1.234],
      [29.125, -1.236],
      [29.123, -1.236],
      [29.123, -1.234]
    ]]
  },
  "properties": {
    "nom": "Zone A",
    "description": "Secteur résidentiel"
  }
}
```

**Exemple avec plusieurs zones (FeatureCollection) :**

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Polygon",
        "coordinates": [[...]]
      },
      "properties": {
        "nom": "Zone A",
        "description": "Secteur Nord"
      }
    },
    {
      "type": "Feature",
      "geometry": {
        "type": "Polygon",
        "coordinates": [[...]]
      },
      "properties": {
        "nom": "Zone B",
        "description": "Secteur Sud"
      }
    }
  ]
}
```

**Exemple avec champ NOM en majuscules (export SIG) :**

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Polygon",
        "coordinates": [[...]]
      },
      "properties": {
        "id": 1,
        "NOM": "45EME C.E.P BUTSILI",
        "CODE": "001",
        "description": "Centre d'éveil préscolaire"
      }
    },
    {
      "type": "Feature",
      "geometry": {
        "type": "Polygon",
        "coordinates": [[...]]
      },
      "properties": {
        "id": 2,
        "NOM": "CAMP DE KANYARUCHINYA",
        "CODE": "002",
        "description": "Camp de déplacés"
      }
    }
  ]
}
```

> **Important** : 
> - Les propriétés `"NOM"` (majuscule), `"nom"` ou `"name"` seront affichées sur la carte
> - Priorité : `"NOM"` > `"nom"` > `"name"`
> - Chaque feature peut avoir son propre nom
> - Utile pour différencier plusieurs zones d'un même site
> - Les exports de logiciels SIG utilisent souvent NOM en majuscules

> **Attention** : N'éditez le GeoJSON que si vous comprenez sa structure. Une erreur peut rendre les données inutilisables.

---

### 💾 Enregistrer les modifications

#### Boutons en bas de la page

```
┌──────────┐  ┌────────────────────────────────┐
│ Annuler  │  │ Enregistrer les modifications  │
└──────────┘  └────────────────────────────────┘
```

**Bouton "Annuler"**
- Retourne à la liste "Mes Sites"
- Aucune modification n'est sauvegardée
- Utile si vous avez fait une erreur

**Bouton "Enregistrer les modifications"**
- Valide et enregistre toutes vos modifications
- GPS, photos, GeoJSON sont mis à jour
- Message de confirmation : ✅ "Site mis à jour avec succès"

---

### 🔍 Rechercher dans mes sites

Sur la page "Mes Sites", utilisez la barre de recherche :

```
┌────────────────────────────────────────────┐
│ 🔍 Rechercher un site...                   │
└────────────────────────────────────────────┘
```

**Comment ça marche :**
1. Tapez quelques lettres du nom du site
2. Les cartes s'adaptent en temps réel
3. Seuls les sites correspondants s'affichent

**Exemples :**
- Tapez "école" → Affiche tous les sites contenant "école"
- Tapez "masisi" → Affiche les sites de Masisi
- Tapez "ABC" → Affiche les sites avec le code ABC

---

### 🗺️ Utiliser la carte interactive

#### Affichage de la carte

La carte montre tous vos sites qui ont des coordonnées GPS et/ou des données GeoJSON.

**Marqueurs 📍**
- Chaque marqueur = un site avec coordonnées GPS
- Couleur : Rouge/Orange (selon le type)
- Clustering automatique si beaucoup de sites proches

**Zones GeoJSON 🗺️**
- Polygones, lignes ou formes géographiques
- Couleur : Bleu avec remplissage semi-transparent
- Représentent les limites ou zones des sites
- Affichées automatiquement si le site contient des données GeoJSON
- **Label permanent** : Le nom s'affiche directement sur chaque polygone
  - Ordre de priorité : `properties.NOM` > `properties.nom` > `properties.name`
  - Si ces champs n'existent pas, affiche le nom du site
  - Fond bleu avec bordure
  - Toujours visible sans cliquer
  - Facilite l'identification rapide des zones

#### Badges visuels sur les cartes de sites

Sur chaque carte de site, vous pouvez voir des badges :
- **GPS** (vert) : Le site a des coordonnées GPS
- **GeoJSON** (bleu) : Le site a des données géographiques complexes
- **Assigné** (violet) : Le site vous est attribué individuellement

#### Interagir avec la carte

**Naviguer :**
- **Zoom** : Molette de la souris, boutons +/-, pincement tactile
- **Déplacer** : Cliquez et glissez
- **Réinitialiser** : Double-clic pour recentrer

**Voir les détails d'un site :**

**Option 1 : Cliquer sur un marqueur GPS 📍**
1. Cliquez sur un marqueur rouge
2. Un popup s'ouvre avec :
   - Nom du site
   - Code du site
   - Localisation
   - Type de site
   - Population
   - Organisation
   - Indication "Zone GeoJSON affichée" (si applicable)
   - Bouton **"Gérer le site"**

**Option 2 : Cliquer sur une zone GeoJSON 🗺️**
1. Cliquez sur un polygone ou une ligne bleue
2. Un popup s'ouvre avec :
   - Nom du site
   - "Zone GeoJSON du site"
   - Propriétés de la zone (nom, description si disponibles)
   - Bouton **"Gérer le site"**

**Accéder directement à l'édition :**
- Depuis n'importe quel popup, cliquez sur "Gérer le site"
- Vous arrivez directement sur la page d'édition

#### Comprendre l'affichage combiné GPS + GeoJSON

Un site peut avoir :
- **Uniquement GPS** : Un marqueur simple
- **Uniquement GeoJSON** : Une zone bleue sans marqueur
- **GPS + GeoJSON** : Un marqueur ET une zone bleue autour/à proximité

> **Astuce** : Les zones GeoJSON sont particulièrement utiles pour les camps de déplacés ou zones de santé qui couvrent une superficie importante.

---

## FAQ - Questions fréquentes

### Questions générales

#### Q1 : Je ne vois aucun site dans "Mes Sites", pourquoi ?

**Réponses possibles :**

1. **Aucun site ne vous a été attribué**
   - Contactez votre super administrateur
   - Demandez l'attribution de sites spécifiques

2. **Votre compte n'est pas actif**
   - Vérifiez avec l'administrateur que votre compte est actif

3. **Vous n'appartenez à aucune organisation**
   - Si vous n'avez pas d'organisation ET aucun site attribué, la page sera vide

---

#### Q2 : Quelle est la différence entre "Mes Sites" et "Master list" ?

| Critère | Mes Sites | Master list |
|---------|-----------|-------------|
| **Contenu** | Sites qui vous sont attribués | Tous les sites de la base |
| **Modification** | Possible (si permission) | Jamais (lecture seule) |
| **Cas d'usage** | Gestion quotidienne | Consultation, exports, stats |

---

#### Q3 : Je vois "Assigné" sur certains sites, qu'est-ce que ça signifie ?

Le badge "Assigné" indique que ce site vous a été **attribué individuellement** par le super admin, en dehors de votre organisation.

**Exemple :**
- Vous travaillez pour l'ONG A
- Vous avez accès aux sites de l'ONG A (normal)
- Le super admin vous attribue 3 sites de l'ONG B pour une mission spéciale
- Ces 3 sites auront le badge "Assigné"

---

### Questions sur les permissions

#### Q4 : Qu'est-ce que la permission "Modification" ?

La permission "Modification" vous autorise à :
- ✅ Changer les coordonnées GPS (latitude, longitude)
- ✅ Ajouter de nouvelles photos
- ✅ Supprimer des photos existantes
- ✅ Modifier les données GeoJSON (contours, zones)

**Sans cette permission :**
- ❌ Tous ces champs sont en lecture seule
- ℹ️ Vous voyez une alerte jaune sur la page

---

#### Q5 : Qu'est-ce que la permission "Collecte" ?

La permission "Collecte" vous donne accès aux données du site pour consultation et collecte de données.

En pratique :
- Vous pouvez voir toutes les informations du site
- Vous pouvez consulter la localisation, les photos, les statistiques
- C'est la permission de base pour accéder au site

> **Note** : Actuellement, "Collecte" et "Modification" vont souvent ensemble, mais le système permet de séparer les deux pour plus de contrôle.

---

#### Q6 : Pourquoi certains champs sont-ils grisés quand je modifie un site ?

**Si vous avez la permission "Modification" :**
- Les champs **modifiables** : GPS, Photos, GeoJSON
- Les champs **non modifiables** (toujours grisés) : Nom du site, Type, Localisation administrative, Population

Ces informations sont gérées centralement et ne peuvent être modifiées que par un administrateur.

**Si vous n'avez PAS la permission "Modification" :**
- Tous les champs sont grisés
- Le site est en lecture seule
- Une alerte jaune vous l'indique en haut de page

---

### Questions techniques

#### Q7 : Quel format utiliser pour les coordonnées GPS ?

**Format attendu :**
- **Latitude** : Nombre décimal entre -90 et 90
  - Exemples valides : `-1.234567`, `0.123456`, `5.5`
  - Exemples invalides : `1° 30' 45"`, `1,234` (virgule au lieu de point)

- **Longitude** : Nombre décimal entre -180 et 180
  - Exemples valides : `29.123456`, `-12.5`, `0`
  - Exemples invalides : `29° 15' 30"`, `360` (hors limites)

**Conversion depuis d'autres formats :**
Si vous avez des coordonnées en degrés/minutes/secondes (DMS), utilisez un convertisseur en ligne pour obtenir le format décimal.

---

#### Q8 : Mes photos sont trop grosses, que faire ?

**Limite de taille :** 5 MB par photo

**Solutions :**

1. **Compresser la photo**
   - Utilisez un outil en ligne gratuit (TinyPNG, CompressJPEG)
   - Ou un logiciel comme GIMP, Paint.NET

2. **Réduire la résolution**
   - Une photo pour le web n'a pas besoin de 4000x3000 pixels
   - 1920x1080 ou 1280x720 suffisent largement

3. **Changer le format**
   - Privilégiez JPEG pour les photos
   - PNG est plus lourd (réservé aux graphiques avec transparence)

---

#### Q9 : La carte n'affiche pas mes sites, pourquoi ?

**Causes possibles :**

1. **Le site n'a pas de coordonnées GPS**
   - Seuls les sites avec latitude ET longitude s'affichent
   - Vérifiez et ajoutez les GPS si vous avez la permission

2. **Coordonnées GPS incorrectes**
   - Si les GPS sont hors limites (ex: lat > 90), le site n'apparaît pas
   - Corrigez les coordonnées

3. **JavaScript désactivé**
   - La carte nécessite JavaScript
   - Vérifiez que JavaScript est activé dans votre navigateur

4. **Connexion Internet lente**
   - Les tuiles de la carte mettent du temps à charger
   - Attendez quelques secondes

---

#### Q10 : J'ai une erreur "JSON invalide" sur le GeoJSON, que faire ?

**Le GeoJSON doit être un JSON valide.**

**Erreurs courantes :**

1. **Virgule finale**
   ```json
   ❌ { "type": "Point", }
   ✅ { "type": "Point" }
   ```

2. **Guillemets manquants**
   ```json
   ❌ { type: "Point" }
   ✅ { "type": "Point" }
   ```

3. **Virgule manquante**
   ```json
   ❌ { "type": "Point" "geometry": {} }
   ✅ { "type": "Point", "geometry": {} }
   ```

**Solution :**
- Utilisez un validateur JSON en ligne
- Copiez votre GeoJSON
- Corrigez les erreurs signalées
- Collez le JSON corrigé

---

#### Q11 : Comment fonctionnent les données GeoJSON sur la carte ?

**Les sites avec données GeoJSON sont affichés automatiquement sur la carte.**

**Ce que vous verrez :**

1. **Zones bleues** : Polygones ou lignes représentant les limites du site
2. **Remplissage semi-transparent** : Pour voir la carte sous le polygone
3. **Labels permanents** : Le nom de chaque zone affiché directement sur le polygone
   - Affiche `properties.NOM`, `properties.nom` ou `properties.name` du GeoJSON
   - Ordre de priorité : `NOM` > `nom` > `name`
   - Si ces champs n'existent pas, affiche le nom du site
   - Fond bleu avec texte blanc pour bonne lisibilité
4. **Popups interactifs** : Cliquez sur la zone pour voir tous les détails

**Différence avec les marqueurs GPS :**
- **Marqueur GPS** = un point précis (rouge/orange)
- **Zone GeoJSON** = une surface ou ligne (bleu)
- Un site peut avoir les deux !

**Exemple de structure GeoJSON avec nom :**
```json
{
  "type": "Feature",
  "geometry": { "type": "Polygon", "coordinates": [[...]] },
  "properties": {
    "nom": "Zone A",
    "description": "Secteur résidentiel"
  }
}
```
Le texte "Zone A" s'affichera directement sur le polygone bleu.

**Utilité :**
- Visualiser l'étendue complète d'un camp
- Délimiter des zones de santé
- Afficher des routes ou trajets
- Marquer des périmètres de sécurité

**Badge visuel :**
Sur la carte du site, un badge bleu "GeoJSON" indique que le site contient des données géographiques qui seront affichées sur la carte interactive.

> **Note** : Si un site n'a que du GeoJSON (pas de GPS), seule la zone bleue sera visible sur la carte, sans marqueur.

---

## Dépannage

### Problème : Je ne peux pas me connecter

**Symptôme :** Message "Identifiants incorrects" ou "Compte désactivé"

**Solutions :**

1. **Vérifier les identifiants**
   - Email correct ? (sensible à la casse)
   - Mot de passe correct ? (sensible à la casse)

2. **Réinitialiser le mot de passe**
   - Cliquez sur "Mot de passe oublié ?"
   - Suivez les instructions par email

3. **Compte désactivé**
   - Contactez votre administrateur
   - Votre compte doit être activé (`is_active = true`)

---

### Problème : "Vous n'avez pas accès à ce site" (erreur 403)

**Symptôme :** Message d'erreur rouge en essayant d'accéder à un site

**Causes possibles :**

1. **Le site ne vous a pas été attribué**
   - Vous avez peut-être copié un lien d'un autre utilisateur
   - Contactez le super admin pour demander l'accès

2. **Votre accès a été révoqué**
   - Le super admin a retiré votre accès
   - Vérifiez avec lui

3. **Le site a été supprimé**
   - Le site n'existe plus dans la base de données

**Solution :** Contactez votre super administrateur pour vérifier vos accès.

---

### Problème : Les photos ne s'affichent pas

**Symptôme :** Icônes de photos cassées ou carrés vides

**Solutions :**

1. **Vérifier le lien symbolique**
   - (Pour l'administrateur système)
   - Commande : `php artisan storage:link`
   - Crée le lien entre `storage` et `public`

2. **Vérifier les permissions des fichiers**
   - Le dossier `storage/app/public` doit être accessible en lecture

3. **Chemins dans la base de données**
   - Les chemins doivent être relatifs : `sites/photos/nom.jpg`
   - Pas de chemins absolus

4. **Rafraîchir la page**
   - Un simple CTRL+F5 peut résoudre un problème de cache

---

### Problème : Mes modifications ne sont pas enregistrées

**Symptôme :** Après avoir cliqué sur "Enregistrer", rien ne change

**Solutions :**

1. **Vérifier les messages d'erreur**
   - Des messages rouges s'affichent en haut de page ?
   - Lisez-les, ils indiquent le problème (GPS invalides, JSON incorrect, etc.)

2. **Vérifier les permissions**
   - Avez-vous la permission "Modification" ?
   - Sans elle, vous ne pouvez pas enregistrer

3. **Problème de connexion Internet**
   - Vérifiez que vous êtes toujours connecté
   - Le formulaire nécessite Internet pour s'enregistrer

4. **Erreur serveur**
   - Contactez l'administrateur système
   - Vérifiez les logs Laravel

---

### Problème : La carte ne charge pas

**Symptôme :** Zone grise à la place de la carte, ou carré vide

**Solutions :**

1. **Attendre le chargement**
   - Les tuiles peuvent prendre 5-10 secondes sur connexion lente

2. **Vérifier la connexion Internet**
   - La carte utilise OpenStreetMap en ligne
   - Sans Internet, pas de carte

3. **Vérifier JavaScript**
   - Ouvrez la console développeur (F12)
   - Y a-t-il des erreurs JavaScript ?

4. **Essayer un autre navigateur**
   - Chrome, Firefox, Edge, Safari

---

## Bonnes pratiques

### Pour les Super Administrateurs

#### ✅ À faire

1. **Documenter les attributions**
   - Notez pourquoi vous avez attribué des sites
   - Gardez une trace des missions temporaires

2. **Utiliser l'attribution en masse**
   - Plus rapide pour les groupes de sites
   - Moins d'erreurs

3. **Vérifier régulièrement**
   - Retirez les accès des missions terminées
   - Audit mensuel des attributions

4. **Utiliser les filtres**
   - Ne perdez pas de temps à chercher manuellement
   - Province → Territoire → Commune

5. **Permissions adaptées**
   - Mission de lecture seule = Décochez "Modification"
   - Collecte terrain complète = Les deux permissions

#### ❌ À éviter

1. **Laisser des accès inutiles**
   - Retirez les accès après les missions
   - Risque de sécurité

2. **Attribuer tous les sites à tout le monde**
   - Perte du contrôle granulaire
   - Utilisez le système d'organisation à la place

3. **Oublier les permissions**
   - Ne pas vérifier les cases à cocher
   - Vérifiez toujours avant d'attribuer

---

### Pour les Utilisateurs

#### ✅ À faire

1. **Vérifier avant d'enregistrer**
   - Relisez les GPS avant de sauvegarder
   - Une erreur de GPS peut déplacer le site de 100 km

2. **Photos de qualité**
   - Photos claires, bien éclairées
   - Cadrées sur l'essentiel
   - Compressées si > 5 MB

3. **Tester sur la carte**
   - Après avoir modifié les GPS, vérifiez sur la carte
   - Le marqueur doit être au bon endroit

4. **Sauvegarder régulièrement**
   - Cliquez sur "Enregistrer" après chaque modification importante
   - Ne faites pas 10 modifications d'un coup

5. **Signaler les problèmes**
   - Site introuvable ? GPS incorrectes ?
   - Informez le super admin rapidement

#### ❌ À éviter

1. **Modifier sans vérifier**
   - Ne changez pas les GPS au hasard
   - Vérifiez avec un GPS terrain si possible

2. **Supprimer des photos importantes**
   - La suppression est définitive
   - Vérifiez avant de cliquer sur 🗑️

3. **Modifier le GeoJSON sans comprendre**
   - JSON invalide = données inutilisables
   - En cas de doute, ne touchez pas

4. **Ignorer les alertes**
   - Message "Lecture seule" ? Vous ne pouvez pas modifier
   - Message d'erreur ? Lisez-le et corrigez

---

### Sécurité et confidentialité

#### 🔒 Points de sécurité

1. **Ne partagez pas vos identifiants**
   - Email et mot de passe sont personnels
   - Chaque action est tracée avec votre nom

2. **Déconnectez-vous**
   - Sur un ordinateur partagé, déconnectez-vous après usage
   - Bouton "Déconnexion" en haut à droite

3. **Mots de passe forts**
   - Minimum 8 caractères
   - Mélange majuscules, minuscules, chiffres, symboles

4. **Signaler les accès non autorisés**
   - Vous voyez des sites que vous ne devriez pas voir ?
   - Informez immédiatement le super admin

---

## Ressources et support

### Contacts

- **Support technique** : contact@dms-cccm.org
- **Administrateur système** : [Insérer contact]
- **Super Administrateur** : [Insérer contact]

### Documentation complémentaire

- **Documentation technique** : `ATTRIBUTION_SITES_UTILISATEURS.md`
- **Guide de gestion des sites** : `GESTION_SITES_ORGANISATIONS.md`
- **Marque et design** : `BRAND_GUIDELINES.md`

---

## Glossaire

**Attribution** : Action d'accorder à un utilisateur l'accès à un site spécifique.

**GeoJSON** : Format de données géographiques utilisé pour décrire des formes géométriques (points, lignes, polygones) avec leurs coordonnées GPS.

**GPS** : Global Positioning System - Coordonnées géographiques (latitude et longitude) qui définissent un point sur la Terre.

**Permission** : Droit accordé à un utilisateur (Modification, Collecte).

**Site** : Emplacement géographique (camp, site de déplacés, infrastructure) géré dans le système.

**Super Administrateur** : Utilisateur avec les droits les plus élevés, peut gérer les attributions de sites.

---

## Historique des versions

**Version 1.0** - 28 mars 2026
- Création du manuel utilisateur
- Documentation complète du système d'attribution
- Guide super admin et utilisateur
- FAQ et dépannage

---

**© 2026 DMS · CCCM · HCR · WNH - Tous droits réservés**
