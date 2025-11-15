# HémiCycle

> Visualisation interactive des données de l'Assemblée nationale française

Application web moderne pour explorer les députés, scrutins et circonscriptions de l'Assemblée nationale. Données synchronisées automatiquement depuis les sources officielles.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vue.js)](https://vuejs.org)

## ✨ Fonctionnalités

-   🗺️ **Carte interactive** des circonscriptions avec GeoJSON
-   🏛️ **Suivi des scrutins** avec statistiques détaillées par parti et département
-   👥 **Profils des députés** avec historique de votes et statistiques
-   📊 **Visualisations** des groupes politiques et participations
-   🔍 **Recherche avancée** par nom, département, code postal
-   ⚡ **Cache Redis** pour performances optimales
-   🔄 **Synchronisation auto** depuis data.assemblee-nationale.fr

## 🚀 Installation

### Prérequis

-   PHP >= 8.2
-   Composer
-   Node.js >= 18
-   MySQL/MariaDB
-   Redis (optionnel, recommandé pour performances)

### Installation rapide

```bash
# Cloner le repository
git clone https://github.com/0xDewey/hemicycle.git
cd hemicycle

# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données dans .env
# DB_CONNECTION=mysql
# DB_DATABASE=hemicycle

# Exécuter les migrations
php artisan migrate

# Synchroniser les données
php artisan data:sync-deputies
php artisan data:sync-votes
php artisan data:sync-circonscriptions

# Compiler les assets et lancer le serveur
npm run build
php artisan serve
```

L'application sera accessible sur `http://localhost:8000`

## 🛠️ Technologies

**Backend**: Laravel 12 • Inertia.js • Redis  
**Frontend**: Vue 3 • Tailwind CSS 4 • shadcn-vue • Chart.js  
**Données**: API Assemblée Nationale (Etalab Open Licence 2.0)

## 📦 Commandes utiles

```bash
# Synchronisation des données
php artisan data:sync-deputies        # Députés actifs
php artisan data:sync-votes          # Scrutins et votes
php artisan data:sync-circonscriptions # GeoJSON des circonscriptions

# Gestion du cache (Redis)
php artisan hemicycle:clear-cache --type=all
php artisan hemicycle:clear-cache --type=deputies
php artisan hemicycle:clear-cache --type=votes

# Développement
composer dev                         # Serveur + Vite en parallèle
npm run dev                          # Vite uniquement
php artisan serve                    # Serveur Laravel uniquement
```

## 📊 Performances

Grâce au cache Redis:

-   Page d'accueil: **~50-100ms** (vs 500-800ms)
-   Détail d'un vote: **~100-200ms** (vs 1-2s)
-   GeoJSON: **~100-150ms** (vs 2-3s)

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à:

-   Ouvrir une issue pour signaler un bug
-   Proposer une pull request pour une amélioration
-   Partager vos idées de fonctionnalités

## 📄 Licence

Ce projet est sous licence [MIT](LICENSE).

Les données de l'Assemblée nationale sont sous licence [Etalab Open Licence 2.0](https://www.etalab.gouv.fr/licence-ouverte-open-licence).

## 🙏 Crédits

Données fournies par [data.assemblee-nationale.fr](https://data.assemblee-nationale.fr/)

---

Développé avec ❤️ pour la transparence démocratique
