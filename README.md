# Edu-Manager

Système de gestion scolaire complet développé avec Laravel 12, permettant la gestion des élèves, parents, professeurs, classes, inscriptions et paiements.

## 📋 Prérequis

- **PHP** >= 8.2
- **Composer** >= 2.0
- **Node.js** >= 20
- **npm** >= 9
- **MySQL** >= 8.0 ou MariaDB >= 10.6
- **Git**

## 🚀 Installation

### 1. Cloner le repository

```bash
git clone https://github.com/amiralmoustaff25-netizen/edu-manger.git
cd edu-manager
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditez le fichier `.env` et configurez votre base de données :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=edu_manager
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Exécuter les migrations

```bash
php artisan migrate
```

### 5. Installer les dépendances Node.js

```bash
npm install
```

### 6. Compiler les assets

```bash
npm run build
```

### 7. Lancer le serveur de développement

```bash
php artisan serve
```

L'application sera accessible sur `http://localhost:8000`.

## 🌱 Seeders & Données de démo

### Exécuter les seeders

```bash
php artisan db:seed
```

Cela va créer :
- Tous les rôles et permissions
- Une année scolaire active (2025-2026)
- Des utilisateurs de démonstration
- Des classes et inscriptions exemples
- Des paiements exemples

## 👥 Utilisateurs de démonstration

| Matricule | Nom | Email | Rôle | Mot de passe |
|-----------|-----|-------|------|-------------|
| 20260325 | Moustapha Diop | moustaff25@gmail.com | Super-Admin | password |
| ADM-260001 | Admin École | admin@edumanager.sn | Admin | password |
| MCO-260001 | Manager Comptable | manager.comptable@edumanager.sn | Manager-Comptable | password |
| CPT-260001 | Comptable | comptable@edumanager.sn | Comptable | password |
| PROF001 | Moussa Sall | moussa@ecole.sn | Professeur | password |
| ELE-260001 | Amadou Diallo | amadou@edumanager.sn | Élève | password |
| ELE-260002 | Aïssatou Ndiaye | aissatou@edumanager.sn | Élève | password |

## 🔐 Matrice des rôles et permissions

### Super-Admin
Accès total à toutes les fonctionnalités du système.

### Admin
Accès complet sauf les fonctions réservées au super-admin :
- Gestion des utilisateurs
- Gestion des élèves
- Inscriptions
- Gestion des parents
- Classes
- Années scolaires
- Paiements
- Logs de connexion

### Manager-Comptable
Gestion financière et comptable :
- ✅ Voir dashboard et profil
- ✅ Voir et enregistrer les paiements
- ✅ Valider les paiements partiels
- ✅ Voir les finances et recouvrement
- ✅ Voir les élèves et parents (lecture seule)

### Comptable
Gestion des paiements (sans validation partielle) :
- ✅ Voir dashboard et profil
- ✅ Voir et enregistrer les paiements
- ✅ Voir les finances et recouvrement
- ✅ Voir les élèves et parents (lecture seule)
- ❌ Valider les paiements partiels

### Professeur
Gestion pédagogique :
- ✅ Voir dashboard et profil
- ✅ Voir sa classe
- ✅ Saisir les notes
- ✅ Marquer les absences
- ✅ Voir les élèves (lecture seule)

### Parent
Espace famille :
- ✅ Voir dashboard et profil
- ✅ Voir ses enfants
- ✅ Voir les paiements de ses enfants
- ✅ Voir les notes de ses enfants

### Élève
Espace personnel :
- ✅ Voir dashboard et profil
- ✅ Voir ses notes
- ✅ Voir ses paiements

## 📚 Liste complète des permissions

### Dashboard & Profil
- `voir-dashboard`
- `voir-profil`
- `modifier-profil`
- `supprimer-compte`

### Utilisateurs
- `voir-utilisateurs`
- `creer-utilisateur`
- `modifier-utilisateur`
- `supprimer-utilisateur`
- `activer-desactiver-utilisateur`
- `reinitialiser-mot-de-passe-utilisateur`

### Élèves
- `voir-eleves`
- `voir-detail-eleve`
- `transferer-eleve`
- `modifier-statut-eleve`

### Inscriptions
- `voir-inscriptions`
- `creer-inscription`

### Parents
- `voir-parents`
- `creer-parent`
- `modifier-parent`
- `voir-detail-parent`
- `archiver-parent`
- `restaurer-parent`
- `supprimer-parent`
- `associer-eleve-parent`
- `dissocier-eleve-parent`
- `reinitialiser-mot-de-passe-parent`

### Classes
- `voir-classes`
- `creer-classe`
- `modifier-classe`
- `supprimer-classe`
- `affecter-professeur`

### Années scolaires
- `voir-annees-scolaires`
- `creer-annee-scolaire`
- `supprimer-annee-scolaire`
- `activer-annee-scolaire`

### Paiements
- `voir-paiements`
- `enregistrer-paiement`
- `valider-paiement-partiel`
- `voir-finances`
- `voir-recouvrement`

### Pédagogie
- `voir-sa-classe`
- `saisir-notes`
- `marquer-absences`

### Espace famille
- `voir-ses-enfants`
- `voir-ses-paiements-enfants`
- `voir-ses-notes-enfants`

### Logs & Audit
- `voir-logs-connexion`
- `voir-detail-log-connexion`

### Super-Admin
- `tout-faire`

## 🧪 Tests

Exécuter la suite de tests :

```bash
php artisan test
```

## 🎨 Stack technique

- **Backend** : Laravel 12, PHP 8.2+
- **Frontend** : Blade, Tailwind CSS 3.4, Alpine.js
- **Base de données** : MySQL 8.0
- **Authentification** : Laravel Breeze
- **Gestion des permissions** : Spatie Laravel Permission
- **Build tools** : Vite 5

## 📝 Structure du projet

```
app/
├── Http/
│   ├── Controllers/     # Contrôleurs
│   ├── Middleware/     # Middleware (auth, roles, etc.)
│   ├── Requests/       # Form Requests
│   └── Policies/       # Policies d'autorisation
database/
├── migrations/         # Migrations de base de données
└── seeders/           # Seeders (données de démo)
resources/
├── views/             # Vues Blade
├── css/               # Assets CSS
└── js/                # Assets JavaScript
routes/
├── web.php            # Routes web
└── auth.php           # Routes d'authentification
```

## 🤝 Contribuer

Les contributions sont les bienvenues ! Merci de suivre ces étapes :

1. Fork le repository
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

## 👨‍💻 Auteur

- **Moustapha Diop** - [amiralmoustaff25-netizen](https://github.com/amiralmoustaff25-netizen)

## 🙏 Remerciements

- Laravel Framework
- Spatie pour le package Laravel Permission
- La communauté Laravel
