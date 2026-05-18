# 📚 Documentation DMS CCCM

Bienvenue dans la documentation du système DMS CCCM. Cette page centralise tous les documents disponibles pour vous aider à utiliser et maintenir l'application.

---

## 🎯 Guide Rapide

### Pour commencer
- 🚀 [README - Guide d'installation](README.md)
- 👤 **[Manuel Utilisateur - Attribution des Sites](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md)** ⭐ *Recommandé*

### Par profil utilisateur

| Profil | Document recommandé |
|--------|---------------------|
| **Utilisateur simple** | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → Section "Guide Utilisateur" |
| **Super Administrateur** | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → Section "Guide Super Administrateur" |
| **Admin Organisation** | [Gestion des Sites par Organisation](GESTION_SITES_ORGANISATIONS.md) |
| **Développeur** | [Documentation Technique](#documentation-technique) |

---

## 📖 Guides Utilisateur

### Manuel Utilisateur Complet

**[📘 Manuel Utilisateur - Système d'Attribution de Sites](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md)**

Le guide le plus complet pour tous les utilisateurs. Plus de 700 lignes de documentation avec :

**Contenu :**
- ✅ Introduction au système (3 niveaux d'accès)
- ✅ Guide Super Administrateur détaillé
  - Vue d'ensemble des utilisateurs
  - Recherche et filtres (Province, Territoire, Commune)
  - Attribution unique et en masse
  - Gestion des permissions
- ✅ Guide Utilisateur détaillé
  - Interface "Mes Sites"
  - Carte interactive
  - Modification de sites (GPS, Photos, GeoJSON)
- ✅ FAQ - 10 questions fréquentes
- ✅ Dépannage étape par étape
- ✅ Bonnes pratiques

**Pour qui ?**
- Utilisateurs finaux collectant des données
- Super administrateurs gérant les accès
- Administrateurs d'organisation

---

## 🔧 Documentation Technique

### Systèmes d'Attribution et Gestion

**[📄 Attribution Individuelle de Sites aux Utilisateurs](ATTRIBUTION_SITES_UTILISATEURS.md)**

Documentation technique du système d'attribution individuelle.

**Contenu :**
- Structure de la base de données (`site_user_access`)
- 3 niveaux d'accès expliqués
- Routes API complètes
- Permissions et contrôles
- Cas d'usage typiques
- Performances et optimisations

**Pour qui ?** Développeurs, administrateurs système, super admins techniques

---

**[📄 Gestion des Sites par Organisation](GESTION_SITES_ORGANISATIONS.md)**

Documentation du système d'accès au niveau organisation.

**Contenu :**
- Attribution de sites aux organisations
- Modification de photos, GPS, GeoJSON
- Routes et contrôleurs
- Migrations de base de données

**Pour qui ?** Développeurs, admins organisations

---

### Design et Branding

**[🎨 Brand Guidelines - Guide des Couleurs](BRAND_GUIDELINES.md)**

Palette de couleurs et directives de design du projet.

**Contenu :**
- Définition des couleurs primaires
- Codes hex et RGB
- Utilisation avec Tailwind CSS

**Pour qui ?** Designers, développeurs frontend

---

### Historique et Modifications

**[📝 Résumé des Modifications Historique](RESUME_MODIFICATIONS_HISTORIQUE.md)**

Historique des modifications et évolutions du système.

**Contenu :**
- Migrations effectuées
- Modifications de structure
- Population de données

**Pour qui ?** Développeurs, administrateurs système

---

**[📊 Historique de Population](HISTORIQUE_POPULATION.md)**

Détails sur l'importation et la population des données.

**Pour qui ?** Administrateurs de données, développeurs

---

### Données de Référence

**[📋 Catégories et Raisons de Mouvements](CATEGORIES_RAISONS_MOUVEMENTS.md)**

Liste des catégories et raisons de mouvements de population.

**Pour qui ?** Administrateurs système, analystes de données

---

**[📊 Référence des Couleurs](COLOR_REFERENCE.md)**

Référence complète des couleurs utilisées dans l'application.

**Pour qui ?** Designers, développeurs frontend

---

## 🎓 Tutoriels par Tâche

### Tâches Super Administrateur

| Tâche | Document | Section |
|-------|----------|---------|
| Attribuer un site à un utilisateur | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Super Admin → Attribution unique |
| Attribuer plusieurs sites en masse | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Super Admin → Attribution en masse |
| Rechercher des sites par localisation | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Super Admin → Rechercher et filtrer |
| Modifier les permissions d'un utilisateur | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Super Admin → Gérer les permissions |
| Retirer l'accès à un site | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Super Admin → Retirer l'accès |

### Tâches Utilisateur

| Tâche | Document | Section |
|-------|----------|---------|
| Voir mes sites assignés | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Utilisateur → Accéder à mes sites |
| Modifier les coordonnées GPS | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Utilisateur → Modifier les GPS |
| Ajouter/supprimer des photos | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Utilisateur → Gérer les photos |
| Modifier les données GeoJSON | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Utilisateur → Modifier GeoJSON |
| Utiliser la carte interactive | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) | Guide Utilisateur → Carte interactive |

### Tâches Admin Organisation

| Tâche | Document | Section |
|-------|----------|---------|
| Gérer les sites de mon organisation | [Gestion Sites Organisation](GESTION_SITES_ORGANISATIONS.md) | Fonctionnalités → Pour les Organisations |
| Modifier les informations d'un site | [Gestion Sites Organisation](GESTION_SITES_ORGANISATIONS.md) | Interface d'édition |

---

## 🐛 Résolution de Problèmes

### Problèmes Courants

| Problème | Solution | Document |
|----------|----------|----------|
| Je ne vois aucun site | Vérifier attributions | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → FAQ Q1 |
| Les photos ne s'affichent pas | Vérifier storage:link | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → Dépannage |
| Erreur 403 - Accès refusé | Vérifier permissions | [Attribution Sites](ATTRIBUTION_SITES_UTILISATEURS.md) → Support et Dépannage |
| JSON invalide sur GeoJSON | Valider le format | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → FAQ Q10 |
| Coordonnées GPS incorrectes | Format décimal requis | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → FAQ Q7 |

---

## 🔍 Recherche Rapide

### Recherche par mot-clé

| Mot-clé | Documents pertinents |
|---------|---------------------|
| **Attribution** | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md), [Attribution Sites](ATTRIBUTION_SITES_UTILISATEURS.md) |
| **Permissions** | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → Permissions, [Attribution Sites](ATTRIBUTION_SITES_UTILISATEURS.md) → Permissions |
| **GPS / Coordonnées** | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → Modifier GPS |
| **Photos** | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → Gérer photos, [Gestion Sites](GESTION_SITES_ORGANISATIONS.md) |
| **GeoJSON** | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → GeoJSON |
| **Carte / Map** | [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → Carte interactive |
| **Organisation** | [Gestion Sites Organisation](GESTION_SITES_ORGANISATIONS.md) |
| **Routes / API** | [Attribution Sites](ATTRIBUTION_SITES_UTILISATEURS.md) → Routes API |
| **Base de données** | [Attribution Sites](ATTRIBUTION_SITES_UTILISATEURS.md) → Structure BDD |
| **Couleurs / Design** | [Brand Guidelines](BRAND_GUIDELINES.md), [Color Reference](COLOR_REFERENCE.md) |

---

## 📊 Diagramme des Systèmes d'Accès

```
┌─────────────────────────────────────────────────────────────┐
│                    DMS CCCM - SYSTÈME D'ACCÈS              │
└─────────────────────────────────────────────────────────────┘

┌──────────────────┐
│  Super Admin     │─┐
│                  │ │
│  • Tous les      │ │
│    sites         │ │
│  • Attribuer     │ │
│    accès         │ │
└──────────────────┘ │
                     │
                     ├──► [Attribution Individuelle]
                     │    ↓
┌──────────────────┐ │    • Utilisateur A → Site 1, 2, 3
│  Admin           │ │    • Utilisateur B → Site 4, 5
│  Organisation    │─┤    • Permissions granulaires
│                  │ │      (Modification / Collecte)
│  • Sites de      │ │
│    l'orga        │ │
│  • Tous les      │ │
│    membres       │ │
└──────────────────┘ │
                     │
                     ├──► [Accès Organisation]
                     │    ↓
┌──────────────────┐ │    • Organisation A → Tous ses sites
│  Utilisateur     │─┘    • Tous les membres y accèdent
│  Simple          │      • Permissions communes
│                  │
│  • Sites         │
│    assignés      │
│  • Sites de      │
│    son orga      │
└──────────────────┘

         │
         ├──► [Mes Sites] /my/sites
         │    • Sites individuels assignés
         │    • Sites de l'organisation
         │
         └──► [Master List] /sites/master-list
              • Tous les sites (lecture seule)
              • Pour consultation et exports
```

---

## 🚀 Démarrage Rapide

### Première utilisation

**Étape 1 : Installation**
1. Suivez le [README - Guide d'installation](README.md)
2. Exécutez les migrations : `php artisan migrate`
3. Créez le lien symbolique : `php artisan storage:link`

**Étape 2 : Prise en main**
1. Connectez-vous en tant que super administrateur
2. Consultez le [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md)
3. Attribuez vos premiers sites

**Étape 3 : Formation des utilisateurs**
1. Partagez le [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md)
2. Montrez la section appropriée selon le rôle
3. Référez à la FAQ pour les questions courantes

---

## 📞 Support et Contact

### Ressources d'aide

1. **Documentation** : Consultez cette page et les documents liés
2. **FAQ** : [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → FAQ
3. **Dépannage** : [Manuel Utilisateur](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md) → Dépannage

### Contact

- **Support technique** : contact@dms-cccm.org
- **Téléphone** : +243 972 902 713

---

## 📝 Contribution à la Documentation

Pour améliorer cette documentation :

1. Identifiez les sections manquantes ou peu claires
2. Proposez des modifications via le canal approprié
3. Ajoutez des exemples pratiques
4. Mettez à jour les captures d'écran si nécessaire

---

## 📅 Dernière Mise à Jour

**Version de la documentation** : 1.0  
**Date** : 28 mars 2026  
**Auteur** : Équipe DMS CCCM

---

**© 2026 DMS · CCCM · HCR · WNH - Tous droits réservés**
