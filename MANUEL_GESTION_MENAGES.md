# Système de Gestion des Ménages

## Vue d'ensemble

Le système de gestion des ménages permet d'enregistrer et de suivre les ménages dans les sites de déplacés avec deux niveaux d'enregistrement :

- **Niveau 1** : Enregistrement rapide avec informations du chef de ménage et nombre de personnes par catégorie
- **Niveau 2** : Enregistrement détaillé de tous les membres individuels du ménage

## Fonctionnalités

### Niveau 1 - Enregistrement Rapide

#### Informations collectées :
- **Chef de ménage** :
  - Nom complet (nom, postnom, prénom)
  - Données biométriques (photo via webcam, empreinte digitale via lecteur Thales)
  - Sexe, âge/date de naissance, lieu de naissance
  - Contact (téléphone, email)
  - État civil
  - Documents d'identité (type et numéro)
  - Nationalité

- **Origine et déplacement** :
  - Province et territoire d'origine
  - Commune et village d'origine
  - Raison du déplacement
  - Date d'arrivée sur le site

- **Composition du ménage** (nombres) :
  - Hommes adultes (18+)
  - Femmes adultes (18+)
  - Garçons (< 18 ans)
  - Filles (< 18 ans)

- **Vulnérabilités** (nombres) :
  - Femmes enceintes
  - Femmes allaitantes
  - Personnes handicapées
  - Personnes âgées (60+)
  - Enfants orphelins
  - Enfants séparés
  - Malades chroniques

- **Conditions de vie** :
  - Type d'abri (tente, bâche, maison en dur, etc.)
  - Accès à l'eau potable
  - Accès aux latrines
  - Accès à l'électricité

- **Assistance reçue** :
  - Kits NFI
  - Assistance alimentaire
  - Soins de santé

### Niveau 2 - Enregistrement Détaillé

Une fois un ménage passé au Niveau 2, vous pouvez enregistrer tous les membres individuellement avec :

- **Informations personnelles** :
  - Nom complet
  - Sexe et âge
  - Photo et empreinte digitale
  - Lien avec le chef de ménage
  - État civil
  - Documents d'identité

- **Éducation et profession** :
  - Niveau d'éducation
  - Scolarisation actuelle
  - Profession

- **Santé et vulnérabilités** :
  - Handicap (avec type)
  - Maladie chronique (avec type)
  - Femme enceinte/allaitante
  - Enfant orphelin/séparé
  - Personne âgée

## Utilisation du système

### 1. Créer un nouveau ménage (Niveau 1)

1. Cliquez sur **"Ménages"** dans le menu principal
2. Cliquez sur **"Nouveau Ménage"**
3. Remplissez le formulaire avec les informations du chef de ménage
4. **Capture de la photo** :
   - Cliquez sur "Démarrer Caméra"
   - Positionnez le chef de ménage devant la webcam
   - Cliquez sur "Capturer"
   - Si la photo ne convient pas, cliquez sur "Reprendre"
5. **Capture de l'empreinte** :
   - Placez le doigt sur le lecteur Thales
   - Cliquez sur "Capturer Empreinte"
   - Attendez que l'empreinte soit validée
6. Remplissez les sections suivantes :
   - Origine et déplacement
   - Composition du ménage (nombres)
   - Vulnérabilités
   - Conditions de vie
   - Assistance reçue
7. Cliquez sur **"Enregistrer le Ménage (Niveau 1)"**

### 2. Passer au Niveau 2

1. Ouvrez la fiche d'un ménage
2. Cliquez sur **"Passer au Niveau 2"**
3. Lisez attentivement l'information sur ce que représente le Niveau 2
4. Cliquez sur **"Confirmer le passage au Niveau 2"**
5. Vous serez redirigé vers le formulaire d'ajout de membres

### 3. Ajouter des membres (Niveau 2)

1. Dans la fiche du ménage au Niveau 2, cliquez sur **"Ajouter un Membre"**
2. Remplissez les informations du membre :
   - Nom complet, sexe, âge
   - Lien avec le chef de ménage
   - Vulnérabilités spécifiques
3. Capturez la photo et l'empreinte du membre (même processus que pour le chef)
4. Cliquez sur **"Enregistrer le Membre"**
5. Répétez pour tous les membres du ménage

### 4. Modifier un ménage ou un membre

1. Ouvrez la fiche du ménage
2. Cliquez sur **"Modifier"** en haut à droite
3. Modifiez les informations nécessaires
4. Cliquez sur **"Mettre à Jour"**

Pour modifier un membre :
1. Dans la liste des membres, cliquez sur **"Modifier"** à côté du membre
2. Modifiez les informations
3. Cliquez sur **"Mettre à Jour"**

## Intégration Biométrique

### Caméra Webcam (Logitech)

Le système utilise l'API WebRTC pour accéder à la webcam Logitech :
- Résolution recommandée : 640x480 minimum
- Format de sauvegarde : PNG en base64
- Les photos sont stockées dans `storage/app/public/households/`

### Lecteur d'empreintes Thales

Le système est préparé pour l'intégration avec le lecteur Thales :
- L'interface JavaScript est prête pour recevoir le SDK Thales
- Les données d'empreintes sont stockées comme chaînes encodées
- Fonction `captureFingerprint()` à personnaliser selon le SDK Thales

**Note** : Pour l'instant, la capture d'empreinte fonctionne en mode simulation. Pour activer le lecteur Thales réel, vous devez :
1. Installer le SDK Thales fourni par le fabricant
2. Modifier la fonction `captureFingerprint()` dans `create.blade.php`
3. Intégrer l'API du lecteur selon la documentation Thales

## Permissions et Accès

- **Collecteurs** : Peuvent créer et modifier les ménages de leurs sites assignés
- **Administrateurs d'organisation** : Ont accès à tous les ménages de leur organisation
- **Super administrateurs** : Ont accès complet et peuvent supprimer des ménages

## Statistiques et Rapports

Le tableau de bord affiche :
- Nombre total de ménages
- Répartition Niveau 1 / Niveau 2
- Nombre total de personnes enregistrées
- Ménages par site
- Taux de vulnérabilité

## Structure de la Base de Données

### Table `households`
Contient les informations des ménages avec 80+ colonnes incluant :
- Informations du chef de ménage
- Données biométriques
- Composition du ménage
- Vulnérabilités
- Conditions de vie

### Table `household_members`
Contient les informations individuelles des membres (Niveau 2) :
- Données personnelles
- Biométrie individuelle
- Éducation et profession
- Santé et vulnérabilités

## Support Technique

Pour toute question ou problème :
1. Vérifiez que la webcam et le lecteur d'empreintes sont correctement connectés
2. Assurez-vous que le navigateur a l'autorisation d'accéder à la webcam
3. Vérifiez que vous avez les permissions nécessaires pour créer des ménages

## Numérotation des Ménages

Chaque ménage reçoit un numéro unique au format : `[CODE_SITE]-[ANNÉE]-[NUMÉRO_SÉQUENTIEL]`

Exemple : `KISH-2026-00001` pour le premier ménage du site de Kishanga en 2026.
