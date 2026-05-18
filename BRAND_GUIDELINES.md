# Charte Graphique DMS CCCM

Ce document décrit la charte graphique appliquée au système DMS CCCM.

## Palette de Couleurs

### Couleur Principale : Primary Blue (#2A87C8)
La couleur principale bleue utilisée pour les éléments interactifs principaux.

- **primary-50**: `#e8f4fb` - Fond très clair
- **primary-100**: `#c2e3f5` - Fond clair
- **primary-200**: `#9ad2ef` - Bordures légères
- **primary-300**: `#72c1e9` - États hover légers
- **primary-400**: `#4ea9db` - Texte secondaire
- **primary-500**: `#2A87C8` - **Couleur principale** (boutons, liens)
- **primary-600**: `#2470ad` - Hover state principal
- **primary-700**: `#1f5a8f` - État actif
- **primary-800**: `#194571` - Texte foncé
- **primary-900**: `#133053` - Texte très foncé

### Couleur Secondaire 1 : Gray (#545456)
Utilisée pour le texte, les bordures et les éléments neutres.

- **secondary-50**: `#f5f5f5`
- **secondary-100**: `#e8e8e9`
- **secondary-200**: `#d1d1d2`
- **secondary-300**: `#b9b9bb`
- **secondary-400**: `#8686a8`
- **secondary-500**: `#545456` - Gris principal
- **secondary-600**: `#4a4a4c`
- **secondary-700**: `#3e3e40`
- **secondary-800**: `#323234`
- **secondary-900**: `#262628`

### Couleur Secondaire 2 : Terracotta (#9d4838)
Pour les alertes, éléments d'attention et accents chaleureux.

- **tertiary-50**: `#fbeae8`
- **tertiary-100**: `#f5ccc5`
- **tertiary-200**: `#eeada2`
- **tertiary-300**: `#e78e7f`
- **tertiary-400**: `#c9685a`
- **tertiary-500**: `#9d4838` - Terracotta principal
- **tertiary-600**: `#8d4032`
- **tertiary-700**: `#75352a`
- **tertiary-800**: `#5d2a22`
- **tertiary-900**: `#451f19`

### Couleur Secondaire 3 : Peach (#d48c74)
Pour les accents doux, notifications et éléments de mise en évidence.

- **accent-50**: `#fdf6f4`
- **accent-100**: `#fae7e1`
- **accent-200**: `#f6d8cd`
- **accent-300**: `#f2c9b9`
- **accent-400**: `#e3aa91`
- **accent-500**: `#d48c74` - Peach principal
- **accent-600**: `#be7d69`
- **accent-700**: `#9f6858`
- **accent-800**: `#7f5347`
- **accent-900**: `#5f3e35`

## Typographie

### Police Principale
- **Famille**: Inter
- **Fallbacks**: Helvetica Neue, Arial, sans-serif
- **Poids disponibles**: 400 (Regular), 500 (Medium), 600 (Semi-Bold), 700 (Bold)

### Utilisation
- **Titres (H1-H6)**: Font-family: Inter, bold ou semi-bold
- **Corps de texte**: Font-family: Inter, regular (400)
- **Boutons et labels**: Font-family: Inter, medium (500) ou semi-bold (600)

## Composants Principaux

### Boutons

#### Bouton Principal
```html
<button class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors shadow-sm">
```

#### Bouton Secondaire
```html
<button class="px-4 py-2 bg-white border-2 border-secondary-300 text-secondary-700 rounded-lg hover:bg-gray-50 transition-colors">
```

#### Bouton Tertiaire (Alerte)
```html
<button class="px-4 py-2 bg-tertiary-600 hover:bg-tertiary-700 text-white rounded-lg font-medium transition-colors shadow-sm">
```

### Cartes (Cards)
```html
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
```

### Navigation Active
Les éléments de navigation actifs utilisent:
- Background: `bg-primary-50 dark:bg-primary-900/20`
- Texte: `text-primary-600 dark:text-primary-400`

### Liens
- Normal: `text-primary-600 dark:text-primary-400`
- Hover: `hover:text-primary-700 dark:hover:text-primary-300`

## Mode Sombre (Dark Mode)

Le système supporte un mode sombre complet avec ajustement automatique des couleurs:

- Fonds: `dark:bg-gray-800`, `dark:bg-gray-900`
- Texte: `dark:text-white`, `dark:text-gray-300`
- Bordures: `dark:border-gray-700`, `dark:border-gray-600`
- Couleurs Primary ajustées: `dark:bg-primary-900/20`, `dark:text-primary-400`

## Gradients

### Page d'accueil et authentification
```css
bg-gradient-to-br from-primary-50 via-white to-accent-50 dark:from-gray-900 dark:to-gray-800
```

## Iconographie

- Style: Outline (Heroicons)
- Taille standard: `w-5 h-5` (20px)
- Taille grande: `w-8 h-8` (32px)
- Couleur: Suit la couleur du texte parent ou utilise les couleurs thématiques (primary, secondary, tertiary, accent)

## Espacement

Utilisation du système d'espacement Tailwind:
- Padding interne: `p-4`, `p-6`, `p-8`
- Margin: `m-2`, `m-4`, `m-6`
- Gap (pour flexbox/grid): `gap-2`, `gap-4`, `gap-6`

## Bordures et Arrondis

- Radius standard: `rounded-lg` (8px)
- Radius complet: `rounded-full` (50%)
- Bordures: `border`, `border-2` avec couleurs gray, primary ou secondary

## Ombres (Shadows)

- Légère: `shadow-sm`
- Standard: `shadow-md`
- Forte: `shadow-lg`
- Extra: `shadow-xl`
- Hover: `hover:shadow-xl` pour effet de profondeur

## Animations et Transitions

Toutes les transitions utilisent:
```css
transition-colors duration-300
```

Pour les animations fluides et cohérentes à travers l'application.

## Accessibilité

- Contraste minimum WCAG AA respecté
- Focus states visibles avec `focus:ring-primary-500`
- Support complet du dark mode
- Texte alternatif pour toutes les images
- Navigation au clavier supportée

## Logo

Le logo DMS CCCM doit être utilisé:
- Format: AVIF (avec fallback)
- Taille standard: `h-10` (40px) dans le header
- Taille grande: `h-16` (64px) sur les pages d'authentification
- Espacement: minimum 16px autour du logo

## Résumé des Couleurs

- **Primary (#2A87C8)** : Bleu - Éléments interactifs principaux, boutons, liens
- **Secondary (#545456)** : Gris - Texte, bordures, éléments neutres
- **Tertiary (#9d4838)** : Terracotta - Alertes, accents chaleureux
- **Accent (#d48c74)** : Peach - Notifications, éléments de mise en évidence

## Mise à jour

Cette charte graphique est basée sur les couleurs spécifiques du CCCM et peut être mise à jour selon les besoins du projet.

Dernière mise à jour: Mars 2026
