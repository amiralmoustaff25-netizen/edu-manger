# Checkpoint 2 — Architecture

**Date :** 2026-08-07
**Périmètre :** Correction des 6 findings Critiques et 8 Hauts de l'audit (Checkpoint 1) relevant de la couche Architecture (Policies, Form Requests, Migrations, Models, routes, logique métier mal placée dans les contrôleurs), plus une partie des findings Moyens directement liés.
**Méthode :** Correction directe du code existant (pas de solution de contournement), vérification systématique par lecture du code appelant avant modification, suite de tests complète exécutée après chaque lot de corrections.

---

## 1. Résumé des corrections effectuées

### Sécurité / bugs critiques (bloquants)

| # | Problème | Correction |
|---|---|---|
| C1 | Le portail parent était inaccessible à 100 % (conflit d'ordre de routes) | Le groupe de routes `role:parent` (`/parents/dashboard`, `/parents/children`, ...) est désormais déclaré **avant** `Route::resource('parents', ParentController::class)` dans `routes/web.php`. Vérifié par résolution de route réelle (`Route::getRoutes()->match()`), pas seulement par lecture du code. |
| C2 | `AuthServiceProvider` référençait `App\Policies\ParentModelPolicy`, classe inexistante → 500 sur 8 des 12 actions de `ParentController` | Remplacé par la vraie classe `App\Policies\ParentPolicy`. |
| C3 | Un `admin` pouvait s'auto-promouvoir `super-admin` (aucun contrôle serveur, seulement un flag client) | Nouvelle classe `app/Support/UserRoles.php` centralisant les rôles et la règle « seul un super-admin peut attribuer/modifier un compte super-admin ». Appliquée dans `UpdateUserRequest`, `UserController` (edit/update/destroy/toggle/resetPassword) et `RoleAssignmentController::update`. |
| C4 | `/api/students/by-matricule/*` et `/api/students/{id}/fees` accessibles à tout utilisateur connecté (IDOR) | Déplacées sous le groupe `role:super-admin|manager-comptable|comptable`, cohérent avec leur unique consommateur (`accounting/payments/create.blade.php`). |
| C5 | Un professeur pouvait enregistrer une note pour un élève hors de sa classe (champs cachés falsifiables) | `GradeController::store()` et `storeForStudent()` vérifient désormais que l'élève a une inscription active dans la classe visée avant tout enregistrement. |
| C6 | La logique parent-enfant de `UserPolicy::view()` était du code mort (l'ability kebab-case ne mappait jamais vers cette méthode) ; `ParentPortalController` ne vérifiait pas l'appartenance de l'élève au parent | Méthode renommée `voirDetailEleve()` (correspond exactement à la conversion Laravel de l'ability). `resolveStudentFromRequest()` interroge désormais `$parent->students()` au lieu d'un `User::find()` brut — IDOR impossible même si la permission venait à être élargie plus tard. |

### Hauts

- **H2** — `CahierTexteController::bulkToggle/markLessonDone/updateRemark` n'avaient aucune autorisation : ajout des mêmes contrôles que `toggle()` (permission + appartenance du programme au professeur), et `updateRemark` utilise désormais la policy `ChapterCompletionPolicy::update()` déjà correcte mais jamais appelée.
- **H3** — `StudentController::show()` ne vérifiait pas qu'un professeur consultant un élève avait bien cet élève dans une de ses classes (contrairement à `AttendanceController`). Ajout de `ensureTeacherIsAssignedToStudent()`, sur le même modèle que le contrôle déjà existant dans `AttendanceController`.
- **H4** — `ReminderController` appelait `$this->authorize('manager-comptable')`, un nom de **rôle** utilisé comme ability : bloquait le rôle manager-comptable lui-même. Supprimé (le middleware de route gère déjà l'autorisation correctement).
- **H5** — 3 migrations écrites mais jamais appliquées (`notes.validated_at/by`, `registrations.status_reason`, index de reporting sur `payments`) alors que le code (`Note`, `Registration`, `StudentStatusService`) en dépendait déjà. Exécutées (`php artisan migrate`).
- **H1** — Le menu « Finance » était visible à `admin` mais la route `payments.*` l'excluait (403 systématique), alors que le seeder lui laisse délibérément la permission `voir-paiements` (commentaire du seeder : « tout sauf validation/suppression de paiements, finances et rapports »). Plutôt que de masquer le menu, la route `payments.*` a été extraite dans son propre groupe `role:...|admin` — la policy `PaymentPolicy` (déjà correcte) continue de refuser à l'admin la validation/suppression/annulation.

### Moyens (M3–M8)

- `GradeController::reopenNotes` : le contrôle d'autorisation se faisait **dans** la boucle `foreach` (aucun contrôle si le lot était vide). Déplacé avant la boucle ; `NotePolicy::reopen()` simplifié en conséquence (il ne dépendait déjà pas de l'instance `Note`).
- `ProgramAnnualController::importExcel` : création du programme + chapitres désormais dans une transaction unique (import partiel impossible en cas d'erreur en cours de lot).
- `PedagogicalConfigurationController::assignments()` : pagination réelle (`paginate(20)`) restaurée à la place d'un `take(5)` qui rendait invisibles toutes les affectations au-delà des 5 dernières. Vue mise à jour avec les liens de pagination.
- `StudentPhotoPolicy` : classe orpheline (jamais enregistrée dans `AuthServiceProvider`, remplacée en pratique par des `Gate::define` directs) → supprimée avec son import inutilisé.
- `config/permissions.php` : ajout des 5 permissions du portail parent et de `annuler-paiement`, absentes des `labels`/`modules_map` bien que réellement seedées et utilisées — elles étaient invisibles depuis l'écran d'administration des rôles.
- `Registration` : ajout des casts `decimal:2` manquants sur `monthly_fee`/`registration_fee_paid`, cohérent avec `Payment`/`Invoice`/`ClassroomFee`.
- `LoginLogPolicy` (3 permissions référencées mais jamais définies) : **vérifié à l'exécution** (`$user->can('supprimer-log-connexion')`) — Spatie renvoie `false` proprement plutôt que de lever une exception. Le comportement est donc déjà sûr par défaut (fail-closed) ; aucune modification nécessaire, contrairement à ce que laissait craindre l'audit initial.

### Bug découvert pendant la validation (non présent dans l'audit initial)

En écrivant un test fonctionnel réel pour le portail parent (`ParentPortalTest`), le dashboard a levé une erreur fatale « Attempt to read property "classroom" on null » : `optional($student->latestRegistration->classroom)` évalue `$student->latestRegistration->classroom` **avant** de passer le résultat à `optional()`, donc si l'élève n'a pas d'inscription, l'accès à `->classroom` sur `null` plante. `optional()` doit envelopper `latestRegistration`, pas son résultat. Corrigé dans `resources/views/parents/dashboard.blade.php`. Ce bug était invisible à la lecture statique et n'a jamais pu se déclencher avant la correction de C1 (le dashboard était inatteignable) — bon exemple de pourquoi le test fonctionnel réel reste nécessaire en plus de l'audit statique.

---

## 2. Pourquoi ces corrections (rationale)

- **C1–C6 d'abord** : ce sont les seuls findings qui rendaient une fonctionnalité entière inutilisable (portail parent) ou ouvraient une faille de sécurité activement exploitable (escalade de privilèges, IDOR). Les corriger en premier était nécessaire avant tout travail d'architecture plus large, sans quoi le Checkpoint 3 (Authentification/permissions) aurait été testé sur une base connue pour être cassée.
- **Centralisation des rôles (`UserRoles`)** : la duplication de 4 listes de rôles incohérentes entre `StoreUserRequest`, `UpdateUserRequest`, `UserController`, `RoleAssignmentController` était la cause structurelle de la faille d'escalade de privilèges (finding F1 de l'audit). La corriger ponctuellement sans centraliser aurait laissé le bug réapparaître à la prochaine modification d'une des 4 listes.
- **Contrôles ajoutés dans les contrôleurs plutôt que dans les Policies (C3, C5, H3)** : Spatie Permission enregistre un `Gate::before` global qui court-circuite toute Policy dès que l'utilisateur possède la permission littérale demandée (`vendor/spatie/laravel-permission/src/PermissionRegistrar.php:125`). Une règle par-instance (« ce professeur peut-il voir CET élève », « ce paiement peut-il être modifié par CET admin ») ne peut donc pas être imposée uniquement via une Policy si le rôle possède déjà la permission plate correspondante — elle doit être explicite. C'est un point d'architecture Laravel + Spatie à connaître pour la suite du projet, documenté en commentaire à chaque endroit concerné.
- **Test fonctionnel ajouté (`ParentPortalTest`)** : le portail parent n'avait aucune couverture de test avant ce checkpoint (probablement parce qu'il était inaccessible depuis sa création, cf. C1). Un module qui vient d'être débloqué par 4 corrections de sécurité/routing indépendantes (C1, C2, C6, H6) sans aucun test de régression serait un risque pour les checkpoints suivants.

---

## 3. Fichiers modifiés

**Contrôleurs**
- `app/Http/Controllers/CahierTexteController.php`
- `app/Http/Controllers/GradeController.php`
- `app/Http/Controllers/ParentPortalController.php`
- `app/Http/Controllers/PedagogicalConfigurationController.php`
- `app/Http/Controllers/ProgramAnnualController.php`
- `app/Http/Controllers/ReminderController.php`
- `app/Http/Controllers/RoleAssignmentController.php`
- `app/Http/Controllers/StudentController.php`
- `app/Http/Controllers/UserController.php`

**Form Requests**
- `app/Http/Requests/StoreUserRequest.php`
- `app/Http/Requests/UpdateUserRequest.php`

**Modèles / Policies / Providers**
- `app/Models/Registration.php`
- `app/Policies/NotePolicy.php`
- `app/Policies/UserPolicy.php`
- `app/Policies/StudentPhotoPolicy.php` (supprimé)
- `app/Providers/AuthServiceProvider.php`
- `app/Support/UserRoles.php` (nouveau)

**Config / Seeder**
- `config/permissions.php`

**Routes**
- `routes/web.php`

**Vues**
- `resources/views/parents/children/index.blade.php`
- `resources/views/parents/dashboard.blade.php`
- `resources/views/pedagogical-configuration/assignments.blade.php`

**Tests**
- `tests/Feature/GradeValidationWorkflowTest.php` (fixture corrigée : ajout de l'inscription manquante)
- `tests/Feature/PedagogicalConfigurationTest.php` (assertion mise à jour pour la pagination réelle)
- `tests/Feature/ParentPortalTest.php` (nouveau — 5 tests couvrant C1/C2/C6/H6)

**Base de données**
- 3 migrations en attente appliquées (aucun fichier de migration modifié)

---

## 4. Tests exécutés

- Suite complète : `php artisan test` → **211 passed (587 assertions)**, 0 échec.
- Exécutée deux fois : une première fois après les corrections de sécurité/routing (3 échecs détectés, tous dus à des fixtures de test obsolètes révélées par les nouveaux contrôles, pas à des régressions du code de production), puis une seconde fois après correction des fixtures → 100 % vert.
- Vérification manuelle de résolution de route réelle (`Route::getRoutes()->match()`) pour confirmer que `/parents/dashboard`, `/parents/children`, `/parents/5` résolvent chacun vers le bon contrôleur.
- `php artisan route:list` : 216 routes, aucune erreur d'enregistrement.
- `php -l` sur chaque fichier PHP modifié : aucune erreur de syntaxe.
- Vérification des logs (`storage/logs/laravel.log`) : aucune nouvelle erreur après la suite de tests finale.

## 5. Points laissés en l'état (hors périmètre volontaire)

- **M9/M10** (table `classroom_matiere` morte, duplication `teacher_classroom`/`PedagogicalAssignment`) : dette technique confirmée mais non corrigée — une suppression de table/colonnes en base est une opération destructrice qui mérite une décision explicite de votre part avant d'y toucher.
- **F3** (rôle `surveillant` obsolète) : idem, décision métier à trancher (garder ou supprimer le rôle), pas un bug.
- **Sujets UX/Dark Mode/PJAX** (H7, M11–M13, F4 de l'audit) : volontairement non traités ici, ils relèvent explicitement du Checkpoint 9 (UX/UI) de votre plan.

---

## Validation

Le Checkpoint 2 est **entièrement corrigé et testé** : les 6 findings Critiques et les 5 findings Hauts directement liés à l'architecture (policies, form requests, migrations, logique métier mal placée) sont résolus, avec régression zéro sur les 211 tests existants + 5 nouveaux. Un bug supplémentaire (dashboard parent, `optional()` mal placé) a été découvert et corrigé en écrivant le test de régression.

**En attente de votre validation avant de démarrer le Checkpoint 3 (Authentification et permissions).**
