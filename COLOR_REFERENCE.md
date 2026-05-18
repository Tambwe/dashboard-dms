# Référence Rapide - Couleurs DMS CCCM

## Couleurs Hexadécimales Principales

| Nom | Valeur Hex | Classe Tailwind | Usage |
|-----|-----------|----------------|-------|
| **Primary Blue** | `#2A87C8` | `primary-500` | Couleur principale - boutons, liens, éléments interactifs |
| **Secondary Gray** | `#545456` | `secondary-500` | Texte secondaire, bordures, éléments neutres |
| **Tertiary Terracotta** | `#9d4838` | `tertiary-500` | Alertes, accents chaleureux, états critiques |
| **Accent Peach** | `#d48c74` | `accent-500` | Notifications douces, mise en évidence |

## Exemples d'Utilisation

### Bouton Principal
```html
<button class="bg-primary-600 hover:bg-primary-700 text-white">
  Cliquez ici
</button>
```

### Badge Alerte
```html
<span class="bg-tertiary-100 text-tertiary-800">
  Important
</span>
```

### Notification Douce
```html
<div class="bg-accent-50 border-accent-200 text-accent-800">
  Information
</div>
```

### Lien
```html
<a href="#" class="text-primary-600 hover:text-primary-700">
  En savoir plus
</a>
```

## Classes CSS Personnalisées

- `.primary-button` - Bouton principal stylisé
- `.icon-primary` - Icône avec couleur primaire
- `.icon-secondary` - Icône avec couleur secondaire
- `.icon-tertiary` - Icône avec couleur tertiaire
- `.card` - Carte avec bordure et ombre
- `.stat-card` - Carte pour statistiques

## Mode Sombre

Toutes les couleurs ont des variantes automatiques en mode sombre :
- `dark:bg-primary-900/20`
- `dark:text-primary-400`
- `dark:border-secondary-700`
