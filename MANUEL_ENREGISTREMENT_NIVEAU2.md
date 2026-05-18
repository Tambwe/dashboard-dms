# Manuel d'Enregistrement des Membres - Niveau 2

## Vue d'ensemble

Le système d'enregistrement de **Niveau 2** permet de capturer les informations détaillées de chaque membre d'un ménage. Contrairement au Niveau 1 qui ne collecte que les informations du chef de ménage et les totaux agrégés, le Niveau 2 enregistre **chaque personne individuellement** avec toutes ses caractéristiques.

## Prérequis

Avant d'enregistrer les membres (Niveau 2), le ménage doit être :
1. **Enregistré au Niveau 1** avec les informations du chef de ménage
2. **Passé au Niveau 2** via l'interface de gestion des ménages

## Sections du Formulaire d'Enregistrement

### 1. Informations Personnelles

Cette section collecte l'identité complète du membre :

#### Champs obligatoires :
- **Nom*** : Nom de famille du membre
- **Sexe*** : Masculin (M) ou Féminin (F)
- **Âge*** : Âge en années (0-150)
- **Lien avec le chef*** : Relation avec le chef de ménage
  - Chef de ménage
  - Époux/Épouse
  - Fils/Fille
  - Père/Mère
  - Frère/Sœur
  - Grand-parent
  - Petit-fils/Petite-fille
  - Oncle/Tante
  - Neveu/Nièce
  - Cousin(e)
  - Autre parent
  - Sans lien

#### Champs optionnels :
- **Postnom** : Deuxième nom
- **Prénom** : Prénom(s)
- **Date de naissance** : Format JJ/MM/AAAA
- **Lieu de naissance** : Ville ou territoire de naissance
- **Nationalité** : Par défaut "Congolaise"
- **État civil** : Célibataire, Marié(e), Divorcé(e), Veuf/Veuve, Union libre

💡 **Astuce** : Si vous entrez une date de naissance, l'âge sera calculé automatiquement !

---

### 2. Documents d'Identité

Enregistrez les documents officiels du membre :

- **Type de document** :
  - Carte électorale
  - Passeport
  - Permis de conduire
  - Attestation
  - Acte de naissance
  - Aucun
  
- **Numéro du document** : Numéro d'identification du document

---

### 3. Biométrie

#### 📷 Capture Photo (Caméra Logitech)

1. Cliquez sur **"Démarrer Caméra"**
2. Autorisez l'accès à la caméra dans votre navigateur
3. Positionnez le membre face à la caméra
4. Cliquez sur **"Capturer"** pour prendre la photo
5. Si la photo ne convient pas, cliquez sur **"Reprendre"**

**Recommandations** :
- Éclairage suffisant
- Fond neutre si possible
- Visage centré et bien visible
- Photo de face, regard vers la caméra

#### 👆 Capture Empreinte Digitale (Lecteur Thales)

1. Placez votre lecteur d'empreinte Thales connecté
2. Cliquez sur **"Capturer Empreinte"**
3. Demandez au membre de placer son doigt sur le lecteur
4. Attendez la confirmation de capture

**Note** : L'empreinte est actuellement simulée. L'intégration avec le SDK Thales sera effectuée prochainement.

---

### 4. Contact

Informations de contact du membre (optionnelles) :
- **Téléphone** : Numéro de téléphone mobile ou fixe
- **Email** : Adresse e-mail

---

### 5. Éducation et Profession

#### Niveau d'éducation :
- Aucun
- Primaire incomplet
- Primaire complet
- Secondaire incomplet
- Secondaire complet
- Universitaire
- Professionnel

#### Profession :
Métier ou activité principale (ex: Agriculteur, Commerçant, Enseignant, Artisan...)

#### Scolarisé actuellement :
Cochez cette case si le membre est actuellement à l'école/université

---

### 6. Vulnérabilités et Santé

Identifiez les vulnérabilités spécifiques du membre :

| Vulnérabilité | Description |
|--------------|-------------|
| **Handicap** | Membre avec handicap moteur, visuel, auditif, mental, etc. |
| **Maladie chronique** | Souffre d'une maladie nécessitant un traitement continu |
| **Femme enceinte** | Membre en état de grossesse |
| **Femme allaitante** | Membre qui allaite un bébé |
| **Enfant orphelin** | Enfant ayant perdu un ou deux parents |
| **Enfant séparé** | Enfant séparé de sa famille |
| **Personne âgée** | Personne de 60 ans et plus |

#### Détails complémentaires :
- Si **Handicap** est coché : Précisez le **type de handicap** (Moteur, Visuel, Auditif, Mental...)
- Si **Maladie chronique** est cochée : Précisez le **type de maladie** (Diabète, Hypertension, VIH, Tuberculose...)

---

### 7. Observations

Zone de texte libre pour toute information complémentaire concernant le membre :
- Situation particulière
- Besoins spécifiques
- Remarques importantes
- Historique médical
- Allergies

---

## Workflow d'Enregistrement

### Étape 1 : Accéder au ménage
1. Allez dans **Ménages** → **Liste des ménages**
2. Cliquez sur le ménage concerné pour voir ses détails
3. Vérifiez que le statut est **"Niveau 2"**

### Étape 2 : Ajouter un membre
1. Cliquez sur le bouton **"Ajouter un Membre"**
2. Remplissez le formulaire d'enregistrement Niveau 2
3. Capturez la photo et l'empreinte digitale
4. Vérifiez toutes les informations
5. Cliquez sur **"Enregistrer le Membre"**

### Étape 3 : Répéter pour tous les membres
Ajoutez tous les membres du ménage un par un jusqu'à ce que le nombre de membres enregistrés corresponde au total déclaré.

---

## Statistiques et Suivi

Une fois tous les membres enregistrés, le système affiche :
- **Nombre total de membres** enregistrés
- **Composition du ménage** : hommes, femmes, garçons, filles
- **Vulnérabilités détectées** par membre
- **Liste complète des membres** avec possibilité de modifier ou supprimer

---

## Modification d'un Membre

Pour modifier les informations d'un membre existant :

1. Accédez à la page de détails du ménage
2. Dans la liste des membres, cliquez sur l'icône **"Modifier"** (crayon)
3. Modifiez les champs nécessaires
4. Vous pouvez changer la photo ou l'empreinte si besoin
5. Cliquez sur **"Mettre à Jour le Membre"**

---

## Gestion du Statut des Membres

Chaque membre peut avoir un des statuts suivants :
- **Actif** : Membre présent dans le ménage
- **Décès** : Membre décédé
- **Départ** : Membre ayant quitté le ménage

Le changement de statut permet de garder un historique complet.

---

## Bonnes Pratiques

### ✅ Recommandations

1. **Vérifiez les informations** avant d'enregistrer
2. **Capturez des photos de qualité** pour une identification claire
3. **Enregistrez tous les membres** sans exception
4. **Mettez à jour les vulnérabilités** régulièrement
5. **Utilisez les observations** pour noter des informations importantes
6. **Vérifiez la cohérence** : total déclaré vs membres enregistrés

### ⚠️ Points d'attention

1. Le **lien avec le chef** doit être précis pour les statistiques familiales
2. Les **dates de naissance** sont préférables aux âges seuls
3. Les **vulnérabilités** doivent être documentées avec précision
4. Les **photos et empreintes** sont importantes pour l'identification

---

## Rapports et Exports

Une fois l'enregistrement Niveau 2 complet, vous pouvez :
- Générer des listes de membres par ménage
- Exporter les données détaillées
- Créer des rapports de vulnérabilités
- Analyser la composition démographique

---

## Support Technique

### Problèmes courants

**La caméra ne démarre pas :**
- Vérifiez que la caméra est connectée
- Autorisez l'accès à la caméra dans les paramètres du navigateur
- Essayez un autre navigateur (Chrome recommandé)

**L'empreinte ne se capture pas :**
- Vérifiez la connexion du lecteur Thales
- Contactez l'administrateur système pour installer le SDK

**Informations non sauvegardées :**
- Vérifiez que tous les champs obligatoires (*) sont remplis
- Vérifiez votre connexion internet

---

## Avantages du Niveau 2

✅ **Identification précise** de chaque personne du ménage  
✅ **Biométrie complète** avec photo et empreinte  
✅ **Suivi des vulnérabilités** individuelles  
✅ **Ciblage des assistances** selon les besoins spécifiques  
✅ **Statistiques détaillées** sur la population  
✅ **Historique complet** de chaque membre  
✅ **Base de données exhaustive** pour les interventions humanitaires

---

**Date de création** : 30 mars 2026  
**Version** : 1.0  
**Système** : Dashboard DMS - Gestion des Ménages
