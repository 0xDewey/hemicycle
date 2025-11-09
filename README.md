# Députés Visibility - Consultation des Députés Français

Application web pour consulter les informations des députés français de l'Assemblée Nationale par département.

## Fonctionnalités

- ✅ Sélection de département
- ✅ Consultation des députés par département
- ✅ Affichage des informations détaillées :
  - Nom et prénom
  - Groupe politique
  - Circonscription
- ✅ Stockage local en base de données (MySQL/SQLite)
- ✅ Synchronisation automatique depuis les données officielles
- ✅ Mentions légales conformes à la licence Etalab Open Licence 2.0

## Technologies Utilisées

### Backend
- **Laravel 12** - Framework PHP
- **Inertia.js** - Stack moderne pour créer des SPAs avec Laravel
- **Laravel Jetstream** - Authentification et gestion des équipes

### Frontend
- **Vue 3** - Framework JavaScript progressif
- **Tailwind CSS 4** - Framework CSS utility-first
- **shadcn-vue** - Composants UI réutilisables
- **Radix Vue** - Primitives UI accessibles
- **Chart.js** & **vue-chartjs** - Bibliothèques de graphiques
- **Lucide Vue** - Icônes

### Source de données
- **data.assemblee-nationale.fr** - API officielle de l'Assemblée Nationale (Open Data)
- Données sous licence **Etalab Open Licence v2.0**

## Installation

### Prérequis
- PHP >= 8.2
- Composer
- Node.js >= 18
- npm

### Étapes d'installation

1. **Cloner le repository**
   ```bash
   git clone <url-du-repo>
   cd deputes-visibility
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Installer les dépendances JavaScript**
   ```bash
   npm install --legacy-peer-deps
   ```

4. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configurer la base de données** (optionnel)
   - Par défaut, Laravel utilise SQLite
   - Pour utiliser MySQL, modifier le fichier `.env`:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=deputes_visibility
     DB_USERNAME=root
     DB_PASSWORD=
     ```

6. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

7. **Synchroniser les données des députés**
   ```bash
   php artisan data:sync-deputies
   ```
   Cette commande télécharge et importe les données des députés depuis l'Assemblée nationale.

8. **Compiler les assets**
   ```bash
   npm run build
   ```

9. **Lancer le serveur de développement**
   ```bash
   php artisan serve
   ```

   Ou utiliser le script de développement complet :
   ```bash
   composer dev
   ```

10. **Accéder à l'application**
    - Ouvrir le navigateur à l'adresse : `http://localhost:8000/deputies`

## Structure du Projet

```
app/
├── Http/Controllers/
│   └── DeputyController.php      # Contrôleur pour les députés
└── Services/
    └── DeputyService.php          # Service pour consommer les APIs

resources/
├── js/
│   ├── Components/
│   │   └── ui/                    # Composants shadcn-vue
│   ├── Pages/
│   │   └── Deputies/
│   │       ├── Index.vue          # Page principale
│   │       └── Partials/          # Composants partiels
│   └── lib/
│       └── utils.js               # Utilitaires (cn)
└── css/
    └── app.css                     # Styles globaux

routes/
└── web.php                         # Routes de l'application
```

## API Endpoints

### Routes Web
- `GET /deputies` - Page principale avec interface utilisateur

### API JSON
- `GET /deputies/api/departments` - Liste de tous les départements
- `GET /deputies/api/departements/{code}/deputes` - Députés d'un département spécifique
- `GET /deputies/api/departements/{code}/stats` - Statistiques d'un département
- `GET /deputies/api/deputes/{slug}` - Informations d'un député spécifique

## Commandes Artisan

### Synchronisation des députés
```bash
# Synchroniser les députés depuis l'Assemblée nationale
php artisan data:sync-deputies

# Ou avec une URL personnalisée si l'URL par défaut ne fonctionne pas
php artisan data:sync-deputies --url=URL_DU_FICHIER_ZIP
```

**Sources recommandées :**
- Visitez https://www.data.gouv.fr/datasets/deputes-actifs
- Ou https://data.assemblee-nationale.fr/

Cette commande :
1. Télécharge le fichier ZIP depuis data.assemblee-nationale.fr
2. Extrait et parse les données JSON
3. Importe ou met à jour les députés dans la base de données
4. Nettoie les fichiers temporaires

### Synchronisation des votes (scrutins)
```bash
# Synchroniser les votes depuis l'Assemblée nationale
php artisan data:sync-votes
```

Cette commande télécharge les scrutins de la XVIIe législature et importe :
- Numéro et date du scrutin
- Titre et description
- Décompte (pour, contre, abstentions)
- Résultat (adopté/rejeté)
- Métadonnées complètes

**URL actuelle :** `https://data.assemblee-nationale.fr/static/openData/repository/17/loi/scrutins/Scrutins.json.zip`

### Automatisation (Cron)

Pour automatiser la synchronisation quotidienne :

```bash
# Synchronisation des députés à 3h du matin
0 3 * * * cd /chemin/vers/projet && php artisan data:sync-deputies >> storage/logs/sync-deputies.log 2>&1

# Synchronisation des votes à 4h du matin
0 4 * * * cd /chemin/vers/projet && php artisan data:sync-votes >> storage/logs/sync-votes.log 2>&1
```

## Données Disponibles

Pour chaque député, les informations suivantes sont disponibles :
- **Nom et prénom**
- **Groupe politique**
- **Circonscription**
- **Département**
- **UID unique** (identifiant officiel)
- **Métadonnées complètes** (stockées en JSON)

## Développement

### Mode développement avec hot-reload
```bash
npm run dev
```

### Build de production
```bash
npm run build
```

### Tests
```bash
php artisan test
```

## Mentions Légales et Conformité

Ce projet utilise les données publiques de l'Assemblée Nationale, disponibles sous licence [Etalab Open Licence v2.0](https://www.etalab.gouv.fr/licence-ouverte-open-licence).

### Conformité
- ✅ Mention de la source de données affichée dans le footer de l'application
- ✅ Lien vers la licence Etalab Open Licence v2.0
- ✅ Indication que le site est indépendant et sans lien officiel avec l'Assemblée nationale
- ✅ Affichage de la date de dernière mise à jour des données

### Attribution
Les données proviennent du portail open data de l'Assemblée nationale : [data.assemblee-nationale.fr](https://data.assemblee-nationale.fr)

## Note Importante

⚠️ **URL de synchronisation** : L'URL du fichier ZIP des députés actifs peut changer selon les législatures. Actuellement configurée pour la XVIe législature. Si la synchronisation échoue, vérifier l'URL sur le portail open data de l'Assemblée nationale.

## Licence

MIT - Projet indépendant utilisant des données publiques sous Etalab Open Licence v2.0
