# Checkpoint 5 — Module Élèves

**Date :** 2026-08-07
**Périmètre :** Inscription, réinscription, dossier élève, parents intégrés, documents, historique, services (cantine/transport/internat), recherche, filtres, statistiques. Vérifié en soumettant les formulaires réels, pas seulement au niveau contrôleur.

---

## 1. Décisions prises avec vous en amont

Deux fonctionnalités listées dans le périmètre du checkpoint n'existaient pas réellement dans le code (juste un bouton trompeur pour l'une, un modèle orphelin pour l'autre). Plutôt que d'improviser, je vous ai posé la question :

- **Documents** → vous avez choisi de construire une vraie fonctionnalité minimale. Fait (détails ci-dessous).
- **Historique de passage/redoublement** (`StudentClassHistory`) → vous avez choisi de laisser de côté pour l'instant. Le modèle et sa migration restent en l'état, documentés comme dette technique, aucune route/vue n'y touche (rien n'est donc « cassé » pour l'utilisateur final).

---

## 2. Nouvelle fonctionnalité : Documents élève

Le bouton « 📄 Documents » de la fiche élève pointait vers `#pedagogie` (la section Notes) au lieu d'une vraie section documents — un bouton qui ne faisait pas ce qu'il annonçait.

**Construit :**
- Table `student_documents` (migration), modèle `StudentDocument`, relation `User::documents()`.
- Types de documents prédéfinis (`App\Support\StudentDocumentType`) : acte de naissance, certificat médical, copie pièce d'identité, certificat de scolarité (année précédente), bulletin (année précédente), autre.
- Stockage sur le disque **privé** (`storage/app/private`, jamais accessible par URL directe) — ces documents contiennent des données personnelles sensibles, contrairement à la photo de profil qui est sur le disque public.
- Téléchargement contrôlé via une route authentifiée qui revérifie l'appartenance du document à l'élève (protection IDOR — un document ne peut pas être téléchargé/supprimé via un mauvais `{student}` dans l'URL, testé explicitement).
- Nouvelle permission `gerer-documents-eleve` (upload/suppression), réservée au même périmètre que l'upload de photo (super-admin + admin) ; la consultation/téléchargement suit la permission déjà existante `voir-detail-eleve` de la fiche élève.
- Types de fichiers acceptés : PDF, JPG, PNG, 5 Mo max — rejet des autres types testé explicitement (ex. `.exe`).
- Bouton « Documents » corrigé pour pointer vers la vraie section.

7 tests dédiés, tous verts (upload, permissions, type de fichier invalide, téléchargement, protection IDOR, suppression).

---

## 3. Vérifications effectuées (aucune anomalie)

- **Inscription** (`registrations.create`/`store`) : création de compte élève + inscription + frais (bibliothèque de tarifs avec dérogation réservée à super-admin/manager-comptable) + option cantine/transport/internat + création ou association de parent — tout fonctionne. Un test manquant a été ajouté : associer un **parent déjà existant** (pas seulement en créer un nouveau) lors de l'inscription, qui n'avait aucune couverture.
- **Réinscription** (`registrations.reenrollSearch`/`storeReenrollment`) : recherche par matricule, réutilisation de l'identité/historique, blocage si déjà inscrit sur l'année active — déjà couvert et fonctionnel.
- **Dossier élève** (fiche complète) : classe, statut, situation financière, transfert de classe, changement de statut (workflow `StudentStatus` avec motif obligatoire pour les statuts sensibles), dérogations tarifaires, historique des paiements, parents & responsables, notes, bulletins, présences, sanctions, et désormais documents — tout vérifié fonctionnel.
- **Parents intégrés** : affichés sur la fiche élève avec lien de parenté, responsable financier, contact d'urgence.
- **Services (cantine/transport/internat)** : cases à cocher fonctionnelles à l'inscription et à la réinscription, calcul du total mensuel en temps réel côté formulaire, montants tirés de la bibliothèque de tarifs par classe.
- **Recherche** : par nom, matricule ou email — testé.
- **Filtres** : par classe et par statut — testés. **Point d'attention (pas un bug, un choix de conception à connaître)** : le filtre « Statut » mélange deux notions indépendantes du code — « Actif »/« En attente » filtrent sur le statut de l'inscription (`Registration.status`), mais « Inactif » filtre sur l'activation du compte (`User.is_active`), un champ différent. Un élève nouvellement inscrit (statut « En attente », compte non activé par défaut) apparaît donc simultanément dans les filtres « En attente » ET « Inactif », ce qui peut surprendre un utilisateur qui s'attend à des catégories mutuellement exclusives. Je ne l'ai pas modifié unilatéralement car cela touche à la sémantique voulue du filtre (fusionner sur un seul champ demanderait de trancher lequel des deux est la bonne source de vérité) — à vous de me dire si ça mérite un correctif.
- **Pagination** : 10 élèves par page, filtres conservés en changeant de page — testé.
- **Statistiques** : les seules statistiques élèves existantes sont les compteurs globaux du tableau de bord général (nombre d'élèves actifs, etc., déjà vérifiés au Checkpoint 3) ; il n'existe pas de page de statistiques dédiée au module Élèves — rien n'y était cassé puisque rien n'existait, donc rien à corriger ici sans nouvelle fonctionnalité explicitement demandée.

### Nettoyage (code mort)

- `StudentController::create()`/`store()`, la route `students.create`/`students.store` et `StoreStudentRequest` : doublon exact du flux `registrations.create`/`store` (même service `StudentEnrollmentService::enroll()`), **jamais utilisé par aucune vue** — le lien « Nouvel élève » de la barre latérale pointe directement vers `registrations.create`. Supprimés.
- `routes.json` et `routes_list.txt` : deux exports statiques de `route:list` datant du 24 juillet, commités par erreur, non référencés par le code et désormais complètement obsolètes (routes modifiées depuis). Supprimés.

---

## 4. Fichiers modifiés

**Nouveaux**
- `database/migrations/2026_08_07_160839_create_student_documents_table.php`
- `app/Models/StudentDocument.php`
- `app/Support/StudentDocumentType.php`
- `app/Http/Controllers/StudentDocumentController.php`

**Modifiés**
- `app/Models/User.php` (relation `documents()`)
- `app/Http/Controllers/StudentController.php` (eager-load documents, suppression code mort)
- `routes/web.php` (routes documents, suppression routes mortes)
- `config/permissions.php`, `database/seeders/RoleAndPermissionSeeder.php` (permission `gerer-documents-eleve`)
- `resources/views/students/show.blade.php` (section Documents, bouton corrigé)

**Supprimés**
- `app/Http/Requests/StoreStudentRequest.php`, `routes.json`, `routes_list.txt`

## 5. Tests

- `tests/Feature/StudentDocumentTest.php` (nouveau, 7 tests)
- `tests/Feature/StudentSearchFilterPaginationTest.php` (nouveau, 3 tests)
- `tests/Feature/RegistrationControllerTest.php` (+1 test : association d'un parent existant)
- Suite complète : **326 passed (797 assertions), 0 échec.**

---

## Validation

Le Checkpoint 5 est entièrement corrigé et testé, avec votre validation explicite sur les deux décisions de périmètre (Documents construit, Historique de passage laissé de côté).

**En attente de votre validation avant de démarrer le Checkpoint 6 (Pédagogie)**, et de votre avis sur le chevauchement du filtre Statut (Actif/En attente vs Inactif).
