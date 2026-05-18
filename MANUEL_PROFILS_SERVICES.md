# Manuel Utilisateur - Profils de Services dans les Sites

> 📋 **[Retour à l'index de la documentation](DOCUMENTATION.md)** | Guide complet sur la collecte des profils de services

## 📖 Table des matières

1. [Introduction](#introduction)
2. [Accès au système](#accès-au-système)
3. [Créer une nouvelle collecte](#créer-une-nouvelle-collecte)
4. [Secteurs de services](#secteurs-de-services)
5. [Workflow de validation](#workflow-de-validation)
6. [Consultation et rapports](#consultation-et-rapports)
7. [FAQ](#faq)

---

## Introduction

### Qu'est-ce que le profil de services ?

Le **profil de services** est un outil de collecte de données qui permet de documenter l'ensemble des services disponibles dans un site de déplacés. Il couvre 6 secteurs essentiels :

| Secteur | Description | Indicateurs clés |
|---------|-------------|------------------|
| 🏥 **Santé** | Services médicaux et sanitaires | Structures, personnel, consultations |
| 📚 **Éducation** | Services éducatifs | Écoles, enseignants, élèves |
| 💧 **WASH** | Eau, assainissement, hygiène | Points d'eau, latrines, douches |
| 🌿 **Environnement** | Gestion environnementale | Déchets, drainage, risques |
| 🏠 **Abri et AME** | Logements et articles ménagers | Abris, distributions AME |
| 👥 **Gestion** | Coordination du site | Comités, partenaires, mécanismes |

### Objectifs

- **Monitoring** : Suivre l'évolution des services dans les sites
- **Planification** : Identifier les besoins et prioriser les interventions
- **Coordination** : Faciliter la collaboration entre partenaires
- **Redevabilité** : Documenter les services fournis aux populations

---

## Accès au système

### Navigation

1. Connectez-vous à l'application
2. Dans le menu latéral, cliquez sur **"Profils de Services"**
3. Vous arrivez sur la page de liste des profils

### Permissions requises

Pour créer et collecter des profils de services :
- ✅ Vous devez avoir accès au site (via votre organisation ou attribution individuelle)
- ✅ Vous devez avoir la permission **"Collecte"** sur le site
- ✅ Votre compte doit être actif

---

## Créer une nouvelle collecte

### Étape 1 : Démarrer une nouvelle collecte

1. Sur la page "Profils de Services", cliquez sur **"Nouvelle collecte"**
2. Ou depuis "Mes Sites", cliquez sur un site puis "Créer un profil de services"

### Étape 2 : Informations générales

```
┌────────────────────────────────────────────┐
│ Site *                                     │
│ Sélectionner un site...              ▼    │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│ Date de collecte *                         │
│ 30/03/2026                                 │
└────────────────────────────────────────────┘
```

- **Site** : Sélectionnez le site pour lequel vous collectez les données
- **Date de collecte** : Date de la visite terrain (par défaut : aujourd'hui)

### Étape 3 : Remplir les secteurs

Pour chaque secteur, cochez la case si le service est disponible, puis remplissez les détails.

---

## Secteurs de services

### 🏥 Santé

**Cochez la case** : "Services de Santé disponibles"

**Données à collecter :**

| Champ | Description | Exemple |
|-------|-------------|---------|
| Structures fonctionnelles | Nombre de centres de santé opérationnels | 2 |
| Personnel médical | Nombre total de personnel soignant | 15 |
| Consultations/mois | Nombre moyen de consultations | 450 |
| Services offerts | Types de services (cases à cocher) | Consultation, Vaccination, Soins prénatals |
| Observations | Commentaires libres | "Manque de médicaments essentiels" |

**Services disponibles (checklist) :**
- ☑ Consultation générale
- ☑ Vaccination
- ☐ Soins prénatals
- ☐ Planning familial
- ☐ Nutrition
- ☐ Laboratoire

---

### 📚 Éducation

**Cochez la case** : "Services d'Éducation disponibles"

**Données à collecter :**

| Champ | Description | Exemple |
|-------|-------------|---------|
| Écoles fonctionnelles | Nombre d'écoles actives | 3 |
| Enseignants | Total des enseignants | 18 |
| Élèves inscrits | Total des élèves | 650 |
| Salles de classe | Nombre de salles | 12 |
| Niveaux offerts | Types d'enseignement (checklist) | Primaire, Secondaire |
| Observations | Commentaires | "Manque de matériel scolaire" |

**Niveaux disponibles (checklist) :**
- ☐ Préscolaire
- ☑ Primaire
- ☑ Secondaire
- ☐ Professionnel

---

### 💧 WASH (Eau, Assainissement & Hygiène)

**Cochez la case** : "Services WASH disponibles"

**Données à collecter :**

| Champ | Description | Norme Sphere | Exemple |
|-------|-------------|--------------|---------|
| Points d'eau | Nombre de points d'eau potable | 1/250 personnes | 8 |
| Litres/personne/jour | Quantité d'eau disponible | Min 15L/j | 18.5 |
| Latrines | Nombre de latrines | 1/20 personnes | 45 |
| Douches | Nombre de douches | 1/50 personnes | 15 |
| Gestion des déchets | Système en place ? | Oui/Non | ☑ Oui |
| Observations | Commentaires | - | "Eau parfois turbide" |

**Indicateur clé** : 
- 🟢 Vert : Normes Sphere respectées
- 🟡 Jaune : En dessous des normes
- 🔴 Rouge : Situation critique

---

### 🌿 Environnement

**Cochez la case** : "Gestion de l'Environnement"

**Éléments en place (checklist) :**
- ☑ Gestion des déchets solides
- ☑ Système de drainage
- ☐ Espaces verts disponibles

**Risques environnementaux identifiés :**
- ☑ Inondation
- ☐ Érosion
- ☐ Éboulement
- ☑ Pollution de l'eau
- ☐ Déforestation
- ☐ Accumulation de déchets

**Observations** : Décrivez les mesures d'atténuation ou les besoins urgents.

---

### 🏠 Abri et AME (Articles Ménagers Essentiels)

**Cochez la case** : "Abris et AME disponibles"

**Données à collecter :**

| Champ | Description | Exemple |
|-------|-------------|---------|
| Logements fonctionnels | Nombre d'abris habitables | 320 |
| Ménages ayant reçu AME | Nombre de ménages bénéficiaires | 280 |
| Types d'abris | Typologie (checklist) | Tente, Habitation durable |
| Articles distribués | AME fournis (checklist) | Couvertures, Ustensiles |
| Observations | Commentaires | "Besoin de réparations" |

**Types d'abris disponibles :**
- ☑ Tente
- ☑ Habitation durable
- ☐ Abri temporaire
- ☐ Maison d'accueil

**Articles ménagers distribués :**
- ☑ Couvertures
- ☑ Ustensiles de cuisine
- ☑ Nattes de couchage
- ☑ Moustiquaires
- ☑ Jerrycans

---

### 👥 Gestion et Coordination du Site

**Cochez la case** : "Gestion et Coordination disponibles"

**Structures en place (checklist) :**
- ☑ Comité de site actif
- ☑ Mécanisme de plainte fonctionnel

**Données à collecter :**

| Champ | Description | Exemple |
|-------|-------------|---------|
| Membres du comité | Taille du comité de gestion | 12 |
| Réunions/mois | Fréquence des réunions | 4 |
| Partenaires actifs | Organisations présentes (checklist) | HCR, UNICEF, ONG locales |
| Observations | Commentaires | "Bonne coordination" |

**Partenaires (checklist) :**
- ☑ HCR
- ☑ UNICEF
- ☐ PAM
- ☐ OMS
- ☑ OCHA
- ☑ ONG locales
- ☐ ONG internationales
- ☑ Gouvernement

---

### Notes générales

Un champ libre pour ajouter toute information complémentaire importante :

```
┌────────────────────────────────────────────┐
│ Notes générales                            │
│                                            │
│ Ajoutez ici toute information              │
│ complémentaire importante...               │
│                                            │
│                                            │
└────────────────────────────────────────────┘
```

**Exemples :**
- Événements récents impactant les services
- Besoins urgents non couverts
- Interventions planifiées
- Contacts clés sur le site

---

## Workflow de validation

### Les 4 statuts d'un profil

| Statut | Badge | Description | Actions possibles |
|--------|-------|-------------|-------------------|
| **Brouillon** | Gris | En cours de rédaction | Modifier, Soumettre, Supprimer |
| **Soumis** | Bleu | En attente de validation | Voir (collecteur), Valider/Rejeter (admin) |
| **Validé** | Vert | Approuvé et finalisé | Voir uniquement |
| **Rejeté** | Rouge | Refusé par l'admin | Voir, comprendre les raisons |

### Processus de collecte

```
┌─────────────┐
│  BROUILLON  │  ← Vous créez et remplissez le formulaire
└──────┬──────┘
       │ [Soumettre pour validation]
       ↓
┌─────────────┐
│   SOUMIS    │  ← Vous ne pouvez plus modifier
└──────┬──────┘
       │
       ├─→ [Valider] → ┌─────────────┐
       │                │   VALIDÉ    │  ✓ Données officielles
       │                └─────────────┘
       │
       └─→ [Rejeter] → ┌─────────────┐
                        │   REJETÉ    │  ✗ À corriger
                        └─────────────┘
```

### Étapes détaillées

**1. Création (Brouillon)**
- Vous créez un nouveau profil
- Remplissez progressivement les secteurs
- Vous pouvez enregistrer et revenir plus tard
- **Actions** : Modifier, Supprimer

**2. Soumission**
- Quand tous les champs sont remplis, cliquez sur **"Soumettre pour validation"**
- Le profil passe en statut "Soumis"
- Vous ne pouvez plus le modifier
- **Notification** : Le super admin est informé

**3. Validation (Super Admin uniquement)**

**Option A : Valider**
```
┌────────────────────────────┐
│     [Valider]              │  ← Clic sur le bouton vert
└────────────────────────────┘
```
- Le profil est approuvé
- Les données deviennent officielles
- Utilisables pour les rapports et statistiques

**Option B : Rejeter**
```
┌────────────────────────────┐
│     [Rejeter]              │  ← Clic sur le bouton rouge
└────────────────────────────┘

Modal apparaît :
┌────────────────────────────────────────┐
│ Raison du rejet *                      │
│                                        │
│ Les données sur le WASH sont           │
│ incohérentes avec la population        │
│ du site. Vérifier les chiffres.        │
│                                        │
│    [Annuler]  [Rejeter]                │
└────────────────────────────────────────┘
```
- Le profil est rejeté
- Une raison est obligatoire
- Le collecteur peut voir pourquoi et corriger

**4. Après validation/rejet**
- ✅ **Validé** : Aperçu en lecture seule
- ❌ **Rejeté** : Le collecteur doit créer un nouveau profil corrigé

---

## Consultation et rapports

### Page de liste

Sur `/service-profiles`, vous voyez tous vos profils :

```
┌──────────────────────────────────────────────────────────────────┐
│ Site              Date      Services              Statut  Actions│
├──────────────────────────────────────────────────────────────────┤
│ Camp Kanyaruchinya 28/03    Santé Éducation WASH  Validé  Voir   │
│                    2026                                           │
├──────────────────────────────────────────────────────────────────┤
│ Camp Bulengo       25/03    Santé WASH Abri/AME   Soumis  Voir   │
│                    2026                                           │
├──────────────────────────────────────────────────────────────────┤
│ Site Masisi        20/03    Tous les services     Brouillon       │
│                    2026                            Modifier       │
└──────────────────────────────────────────────────────────────────┘
```

### Filtres disponibles

**1. Recherche par nom de site**
```
┌────────────────────────────────────────┐
│ 🔍 Nom du site...                      │
└────────────────────────────────────────┘
```

**2. Filtre par statut**
```
┌────────────────────────────────────────┐
│ Statut                            ▼    │
│ ─────────────────────────────────────  │
│ Tous                                   │
│ Brouillon                              │
│ Soumis                                 │
│ Validé                                 │
│ Rejeté                                 │
└────────────────────────────────────────┘
```

### Page de détails

Cliquez sur **"Voir"** pour afficher le profil complet :

**En-tête**
- Nom du site et localisation
- Date de collecte
- Collecteur
- Statut avec badge coloré

**Vue d'ensemble**
- Badges colorés pour chaque service disponible
- Résumé visuel rapide

**Détails par secteur**
- Chaque secteur disponible est détaillé dans sa propre carte
- Indicateurs clés en gras
- Listes à puces pour les services/options
- Observations en texte complet

---

## FAQ

### Questions générales

**Q : Dois-je remplir tous les secteurs ?**
R : Non, cochez uniquement les secteurs où des services sont disponibles. Si un secteur n'existe pas, laissez-le décoché.

**Q : Puis-je enregistrer un brouillon et revenir plus tard ?**
R : Oui, cliquez sur "Enregistrer" pour sauvegarder. Le profil reste en brouillon tant que vous ne le soumettez pas.

**Q : Que se passe-t-il si mon profil est rejeté ?**
R : Vous verrez la raison du rejet dans les détails du profil. Créez un nouveau profil avec les corrections demandées.

**Q : Combien de profils puis-je créer par site ?**
R : Autant que nécessaire. Il est recommandé de faire une collecte mensuelle ou trimestrielle selon les besoins.

### Validation des données

**Q : Comment savoir si mes données sont cohérentes ?**
R : Vérifiez que :
- Les ratios WASH sont proches des normes Sphere
- Le nombre d'élèves correspond au nombre de salles
- Les services correspondent à la population du site

**Q : Dois-je attendre la validation pour créer un nouveau profil ?**
R : Non, vous pouvez créer plusieurs profils même si d'autres sont en attente de validation.

### Permissions

**Q : Pourquoi ne puis-je pas créer de profil pour un site ?**
R : Vérifiez que :
- Vous avez accès au site (organisation ou attribution)
- Vous avez la permission "Collecte"
- Le site existe dans la base de données

**Q : Qui peut valider les profils ?**
R : Seul le **Super Administrateur** peut valider ou rejeter les profils soumis.

---

## Bonnes pratiques

### Avant la collecte terrain

✅ **Préparer** :
- Liste des indicateurs à collecter
- Outils de mesure (comptage, observation)
- Contacts des responsables du site

✅ **Coordonner** :
- Informer les partenaires de votre visite
- Planifier des rencontres avec les comités
- Vérifier les données historiques

### Pendant la collecte

✅ **Observer** :
- Vérifier physiquement les infrastructures
- Compter les structures fonctionnelles
- Noter les observations qualitatives

✅ **Interroger** :
- Échanger avec les gestionnaires
- Consulter les registres (santé, éducation)
- Recueillir les besoins exprimés

### Après la collecte

✅ **Vérifier** :
- Cohérence des chiffres entre eux
- Concordance avec la population du site
- Complétude des observations

✅ **Soumettre rapidement** :
- Ne pas attendre plusieurs jours
- Les données sont plus fiables si fraîches
- Facilite le suivi temporel

---

## Indicateurs de référence

### Normes Sphere (WASH)

| Indicateur | Norme minimale | Cible |
|------------|----------------|-------|
| Eau (litres/personne/jour) | 15 L | 20 L |
| Points d'eau | 1/250 personnes | 1/200 |
| Latrines | 1/20 personnes | 1/15 |
| Douches | 1/50 personnes | 1/40 |
| Distance au point d'eau | < 500m | < 200m |
| Temps d'attente | < 30 min | < 15 min |

### Ratios éducation

| Indicateur | Recommandation |
|------------|----------------|
| Élèves/enseignant | 40-50 |
| Élèves/salle de classe | 40-45 |
| Ratio filles/garçons | 50/50 idéalement |

### Santé

| Indicateur | Recommandation |
|------------|----------------|
| 1 centre de santé | 10 000 personnes |
| 1 infirmier | 5 000 personnes |
| Consultations/personne/an | 2-3 |

---

## Contacts et support

- **Support technique** : support@dms-cccm.org
- **Questions sur la collecte** : cccm@unhcr.org
- **Documentation complète** : [DOCUMENTATION.md](DOCUMENTATION.md)

---

**© 2026 DMS · CCCM · HCR · WNH - Tous droits réservés**
