# Deploiement gratuit sur Render

Ce guide publie l'application Laravel sur Render (plan gratuit) avec une base PostgreSQL gratuite (Render ou Neon).

## 1) Preparer le code

Les fichiers suivants sont deja ajoutes pour le deploiement :

- Dockerfile
- render.yaml

Pousse le projet sur GitHub (branche main de preference).

## 2) Creer le service web Render

1. Ouvre Render et connecte ton compte GitHub.
2. Clique sur New + puis Blueprint.
3. Selectionne le repository dashboard-dms.
4. Render detecte automatiquement le fichier render.yaml.
5. Lance la creation du service.

## 3) Creer la base de donnees gratuite

Option A (simple) : PostgreSQL sur Render.

Option B (souvent plus stable) : Neon PostgreSQL gratuit.

Ensuite, recupere les informations DB et renseigne ces variables sur Render :

- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

## 4) Variables d'environnement obligatoires

Dans les variables Render, ajoute aussi :

- APP_URL = URL publique Render (exemple: https://dashboard-dms.onrender.com)
- APP_KEY = cle Laravel generee localement avec la commande ci-dessous

Commande pour generer la cle localement :

```bash
php artisan key:generate --show
```

Copie la valeur affichee dans APP_KEY.

## 5) Initialiser la base

Une fois le premier deploiement termine, ouvre Shell dans Render et execute :

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6) Notes importantes

- Le plan gratuit peut "s'endormir" apres inactivite.
- Si tu utilises des WebSockets Node, le service Node doit etre deploie separement (deuxieme service Render ou fournisseur dedie).
- Si tu veux du temps reel sans gerer un service Node, utilise Pusher (offre gratuite).

## 7) Verification rapide

1. Ouvrir l'URL publique.
2. Verifier le dashboard principal.
3. Tester les filtres et pages critiques.
4. Verifier les logs Render en cas d'erreur 500.
