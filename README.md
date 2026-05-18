# Dashboard DMS CCCM

Un tableau de bord moderne pour le suivi des personnes déplacées internes en République Démocratique du Congo, inspiré du design de [CCCM DMS](https://cccm-dms.vercel.app/).

## 🎨 Fonctionnalités

- ✅ Dashboard moderne avec design similaire au site de référence
- ✅ Mode sombre/clair
- ✅ Graphiques interactifs (ApexCharts)
- ✅ Système de filtres avancé
- ✅ Cartes statistiques
- ✅ Navigation responsive
- ✅ Design avec Tailwind CSS
- ✅ Interface réactive avec Alpine.js

## 📋 Prérequis

- PHP >= 8.1
- Composer
- Node.js >= 18
- NPM ou Yarn

## 🚀 Installation

### 1. Cloner le projet ou utiliser le projet existant

```bash
cd dashboard-dms
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configurer l'environnement

```bash
# Copier le fichier .env (si ce n'est pas déjà fait)
cp .env.example .env

# Générer la clé de l'application
php artisan key:generate
```

### 4. Installer les dépendances JavaScript

```bash
npm install
```

### 5. Compiler les assets

Pour le développement :
```bash
npm run dev
```

Pour la production :
```bash
npm run build
```

### 6. Configurer la base de données

Modifiez le fichier `.env` avec vos informations de base de données :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dashboard_dms
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Exécuter les migrations (optionnel)

```bash
php artisan migrate
```

### 8. Démarrer le serveur de développement

```bash
php artisan serve
```

Visitez `http://localhost:8000` dans votre navigateur.

## 🎨 Structure du Projet

```
dashboard-dms/
├── app/
│   └── Http/
│       └── Controllers/
│           └── DashboardController.php
├── resources/
│   ├── css/
│   │   └── app.css          # Styles Tailwind CSS
│   ├── js/
│   │   └── app.js           # Alpine.js & ApexCharts
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php    # Layout principal
│       └── dashboard.blade.php   # Page du dashboard
├── routes/
│   └── web.php
├── tailwind.config.js
├── postcss.config.js
└── package.json
```

## 🎯 Fonctionnalités du Dashboard

### Filtres
- Province
- Territoire
- Zone de santé
- Site
- Coordinateur
- Gestionnaire
- Mécanisme CCCM
- Date

### Statistiques
- Total PDI
- Hommes / Femmes
- Personnes vivantes avec handicap
- Ménages
- Enfants / Adultes / Personnes âgées

### Graphiques
1. **Répartition par tranche d'âge** (Graphique en barres)
2. **Distribution par sexe** (Graphique en donut)
3. **Distribution par provinces** (Graphique en barres horizontales)
4. **Tendances de déplacement** (Graphique linéaire)

## 🌙 Mode Sombre

Le mode sombre est activé automatiquement selon les préférences du système. Vous pouvez le basculer manuellement avec le bouton en haut à droite.

## 📚 Documentation

**➡️ [📋 INDEX COMPLET DE LA DOCUMENTATION](DOCUMENTATION.md)** ⭐

Page centralisée avec :
- 🎯 Guide rapide par profil utilisateur
- 📖 Tous les guides utilisateur et techniques
- 🎓 Tutoriels par tâche (super admin, utilisateur, admin organisation)
- 🐛 Résolution de problèmes courants
- 🔍 Recherche rapide par mot-clé
- 📊 Diagrammes des systèmes

### Accès Rapide

**Guides Utilisateur**

- **[Manuel Utilisateur - Attribution de Sites](MANUEL_UTILISATEUR_ATTRIBUTION_SITES.md)** 📖
  - Guide complet pour super administrateurs et utilisateurs (700+ lignes)
  - Instructions pas à pas avec exemples visuels
  - FAQ et résolution de problèmes

**Documentation Technique**

- **[Attribution Individuelle de Sites](ATTRIBUTION_SITES_UTILISATEURS.md)** 🔧
  - Documentation technique du système d'attribution
  - Structure de la base de données
  - Routes API et contrôleurs
  
- **[Gestion des Sites par Organisation](GESTION_SITES_ORGANISATIONS.md)** 🏢
  - Système d'accès au niveau organisation
  - Gestion des photos, GPS et GeoJSON
  
- **[Guide des Couleurs et Branding](BRAND_GUIDELINES.md)** 🎨
  - Palette de couleurs du projet
  - Directives de design
  
- **[Historique et Modifications](RESUME_MODIFICATIONS_HISTORIQUE.md)** 📝
  - Résumé des modifications du système
  - Historique de population des données

## 🎨 Personnalisation

### Couleurs

Modifiez `tailwind.config.js` pour personnaliser les couleurs :

```javascript
theme: {
  extend: {
    colors: {
      primary: {
        // Vos couleurs personnalisées
      },
    },
  },
}
```

### Graphiques

Les graphiques sont configurés dans `resources/views/dashboard.blade.php`. Vous pouvez les personnaliser en modifiant les options ApexCharts.

## 📝 TODO / Améliorations futures

- [ ] Ajouter l'authentification
- [ ] Intégrer une vraie carte géographique (Leaflet ou Mapbox)
- [ ] Connexion à une base de données réelle
- [ ] API pour les données en temps réel
- [ ] Export des données en PDF/Excel
- [ ] Notifications en temps réel
- [ ] Multilingue (Français/Anglais)

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request.

## 📄 Licence

Ce projet est open source et disponible sous la [licence MIT](LICENSE).

## 👥 Crédits

Design inspiré de [CCCM DMS](https://cccm-dms.vercel.app/)

---

**Note:** Ce projet est un template de démarrage. Les données affichées sont des exemples et doivent être remplacées par de vraies données provenant d'une base de données ou d'une API.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects. For more information, visit [laravel.com](https://laravel.com).
