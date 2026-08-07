# Checkpoint 1 — Audit général (edu-manager)

**Date :** 2026-08-07
**Périmètre :** Audit en lecture seule de l'intégralité du code applicatif (routes, contrôleurs, modèles, migrations, policies, permissions, vues Blade, sécurité), y compris le travail non commité en cours (portail parent, notifications, saisie de notes par matricule).
**Méthode :** 5 audits indépendants (routes/contrôleurs, modèles/migrations, permissions/policies, vues/UX, sécurité) + vérification croisée. Aucune modification de code à ce stade.

---

## Comment lire ce rapport

- **Critique** : crash (500), fonctionnalité totalement inaccessible, ou faille de sécurité exploitable immédiatement.
- **Haute** : faille de sécurité ou bug fonctionnel majeur, exploitable sous certaines conditions ou touchant un module entier.
- **Moyenne** : bug fonctionnel réel, incohérence notable, dette technique à risque.
- **Faible** : code mort, incohérence cosmétique, dette technique mineure.

Chaque item indique le(s) fichier(s) concerné(s) et l'impact concret. Aucun correctif n'est appliqué dans ce rapport — c'est l'objet des checkpoints suivants (2 : Architecture, 3 : Auth/permissions, etc.).

---

## 🔴 CRITIQUE

### C1. Portail Parent : navigation cassée à 100% (conflit d'ordre de routes)
**Fichier :** `routes/web.php:338` vs `routes/web.php:374-386`

`Route::resource('parents', ParentController::class)` (réservé à `role:super-admin|admin`) est déclarée **avant** le groupe du portail parent (`role:parent`). Elle définit `/parents/{parent}` (show), qui — Laravel matchant les routes dans l'ordre de déclaration — **intercepte toutes les routes à un seul segment** du portail : `/parents/dashboard`, `/parents/children`, `/parents/messaging`, `/parents/calendar`.

**Impact :** un parent qui clique sur "Tableau de bord", "Mes enfants", "Messagerie" ou "Calendrier" tombe sur le middleware `role:super-admin|admin` de la route admin (qu'il ne possède pas) → **403 immédiat, avant même d'atteindre `ParentPortalController`**. Seules les sous-pages à deux segments (`/parents/children/profile`, `/notes`, etc.) sont techniquement atteignables, mais rien n'y mène puisque les points d'entrée sont bloqués. **Le module portail parent, actuellement en développement, est inutilisable à 100 % via la navigation normale.**

### C2. `ParentModelPolicy` inexistante → crash fatal (500) sur tout le module Parents
**Fichier :** `app/Providers/AuthServiceProvider.php:19,35`

```php
use App\Policies\ParentModelPolicy;
...
ParentModel::class => ParentModelPolicy::class,
```
Cette classe n'existe pas — la vraie policy s'appelle `App\Policies\ParentPolicy`. Confirmé en direct (`php artisan model:show ParentModel` lève `Target class [App\Policies\ParentModelPolicy] does not exist`).

**Impact :** dès qu'une autorisation porte sur une instance `ParentModel`, Laravel lève une exception fatale (500, pas un 403). Cela casse **8 des 12 actions** de `ParentController` : `show`, `edit`, `archive`, `restore`, `destroy`, `attachStudent`, `detachStudent`, `resetPassword` — pour **tout le monde, y compris le super-admin**. Seuls `index` et `create` fonctionnent. *(Confirmé indépendamment par 3 audits distincts.)*

### C3. Escalade de privilèges : un `admin` peut se transformer en `super-admin`
**Fichiers :** `app/Http/Requests/UpdateUserRequest.php:10-18`, `app/Http/Controllers/UserController.php:105-124`, `app/Http/Controllers/RoleAssignmentController.php:52-75`, `app/Policies/UserPolicy.php`

- `UpdateUserRequest::ROLES` inclut `'super-admin'` (contrairement à `StoreUserRequest` qui l'exclut à la création — l'exclusion n'est donc que partielle).
- `UserController::update()` fait `$user->syncRoles([$validated['role']])` sans aucune règle hiérarchique. `UserPolicy` ne définit **pas** de méthode `update()` : l'autorisation retombe sur la permission plate `modifier-utilisateur`, possédée par le rôle `admin`.
- `RoleAssignmentController` protège l'attribution du rôle super-admin uniquement par un flag **envoyé par le client** (`confirm_super_admin=1`), trivialement falsifiable.

**Impact :** n'importe quel compte `admin` peut ouvrir la fiche de n'importe quel utilisateur (y compris le sien via un compte complice), sélectionner `super-admin` dans le formulaire, et devenir super-admin. Escalade de privilèges verticale complète. *(Confirmé indépendamment par 3 audits.)*

### C4. IDOR — API élèves non protégée expose les données de tous les élèves
**Fichier :** `routes/web.php:156-157`, `app/Http/Controllers/Api/StudentController.php:15-95`

```php
Route::get('/api/students/by-matricule/{matricule}', ...);
Route::get('/api/students/{registrationId}/fees', ...);
```
Uniquement protégées par `auth/verified/password.changed` — **aucun** contrôle rôle/permission/policy. Matricules prévisibles (`ELE-0001`, `ELE-0002`…), `registrationId` en auto-increment.

**Impact :** tout utilisateur authentifié (y compris un élève ou un parent) peut énumérer les identifiants et récupérer nom, classe, parents, paiements et situation financière complète de **n'importe quel élève de l'établissement**.

### C5. IDOR — saisie de notes par matricule sans vérifier l'inscription réelle de l'élève
**Fichier :** `app/Http/Controllers/GradeController.php` (méthode `storeForStudent`, code nouveau +138 lignes), `resources/views/teachers/grades/student.blade.php:59-62`

Le contrôleur vérifie que la matière est bien affectée au professeur, mais **ne vérifie jamais** que l'élève (`user_id`, champ caché du formulaire) est réellement inscrit dans `classroom_id`.

**Impact :** un professeur peut, en modifiant le champ caché `user_id` (devtools/curl), enregistrer une note pour **n'importe quel élève de l'établissement**, pas seulement les siens. Non couvert par `GradeEntryByMatriculeTest` (6/6 verts, mais ce cas précis n'est pas testé).

### C6. IDOR latent dans le portail parent — aucune vérification d'appartenance parent-enfant
**Fichiers :** `app/Http/Controllers/ParentPortalController.php:25-33`, `app/Policies/UserPolicy.php:7-29`, `app/Http/Controllers/StudentController.php:126-128`

`resolveStudentFromRequest()` fait `User::find($studentId)` depuis un simple `?student=` **sans jamais vérifier** que l'élève appartient au parent connecté. Ce trou est aujourd'hui masqué par un bug différent : `childProfile/childNotes/childAttendances/childDiscipline/childPayments` délèguent à `StudentController::show()`, qui exige la permission `voir-detail-eleve` — **jamais accordée au rôle `parent`** (donc 403 systématique aujourd'hui, régression fonctionnelle pure).

**Root cause :** `UserPolicy::view()` contient la bonne logique métier (self / staff / lien parent-enfant), mais elle est **du code mort** : l'ability `voir-detail-eleve` (kebab-case) est convertie par Laravel en méthode `voirDetailEleve`, qui n'existe pas sur la policy → la résolution retombe systématiquement sur le Gate plat de Spatie, qui ignore complètement le modèle ciblé.

**Danger :** si un développeur "corrige" naïvement en ajoutant `voir-detail-eleve` au rôle `parent` (réparation la plus intuitive), l'IDOR devient **immédiatement exploitable** : un parent authentifié pourra consulter la fiche complète (notes, absences, sanctions, finances) de n'importe quel élève en changeant l'ID dans l'URL.

---

## 🟠 HAUTE

### H1. Menu « Finance » visible aux admins mais 100 % des liens y mènent à un 403
`routes/web.php:165` réserve tout le module finance à `role:super-admin|manager-comptable|comptable` (le rôle `admin` n'y figure pas), alors que `config/sidebar.php:41-58` et `AdminController.php:43-54` affichent ce menu complet au rôle `admin`. Menu mort pour ce rôle.

### H2. Cahier de textes : actions sans AUCUNE autorisation
`app/Http/Controllers/CahierTexteController.php` — `bulkToggle()`, `markLessonDone()`, `updateRemark()` n'ont ni `authorize()`, ni vérification de rôle/permission, ni vérification d'affectation prof↔classe. Seule `toggle()` est protégée. Routes uniquement sous `auth` générique (`routes/web.php:208`).
**Impact :** n'importe quel utilisateur connecté — y compris un élève ou un parent — peut cocher/décocher des chapitres ou modifier des remarques sur le cahier de textes de n'importe quelle classe.

### H3. `StudentController::show/edit` — pas de vérification d'affectation prof↔classe
Contrairement à `AttendanceController`/`GradeController`, aucune vérification que l'élève consulté appartient à une classe du professeur connecté. Un professeur peut consulter le dossier complet (financier, disciplinaire) d'un élève qu'il n'a jamais eu en classe.

### H4. `ReminderController` — ability invalide, le rôle visé (`manager-comptable`) ne peut pas utiliser sa propre fonctionnalité
`$this->authorize('manager-comptable')` utilise un **nom de rôle** comme ability, ce qui n'est pas une permission valide. Résultat : tout `manager-comptable` (non super-admin) reçoit un 403 sur `index/create/store/destroy/generateOverdue/generateUpcoming` — module Rappels de facto réservé au super-admin.

### H5. Migrations récentes non appliquées alors que le code en dépend déjà
`2026_08_04_150000_add_validation_fields_to_notes_table`, `..._153000_add_status_reason_to_registrations_table`, `..._160000_add_reporting_indexes_to_payments_table` sont **`Pending`** (`php artisan migrate:status`), alors que `Note::validate()/reopen()`, `Registration::status_reason`, `StudentStatusService` les utilisent déjà. Erreur SQL "column not found" dès qu'on valide une note ou change le statut d'un élève.

### H6. Dashboard parent réel affiche des compteurs à zéro
Le chemin réellement utilisé par la navigation (`parents.dashboard` → `ParentPortalController::dashboard()`) ne fait pas le `withCount(['notes','attendances'])` que fait la closure `/dashboard` générique — la vue utilise ces compteurs, donc ils sortent vides/à zéro pour tout parent.

### H7. PJAX casse les scripts liés à `DOMContentLoaded`
`resources/js/pjax.js` ne réexécute jamais les listeners `DOMContentLoaded` après une navigation interne. Impact concret : les graphiques ApexCharts du dashboard et le formulaire de paiement (`accounting/payments/create`) restent silencieusement vides après un clic dans le menu ; seul un rechargement complet (F5) contourne le bug — difficile à repérer en test rapide.

### H8. Cascade de suppression non maîtrisée sur toute la chaîne financière
`Registration` n'a **aucun** `SoftDeletes`, alors que `payments`, `invoices`, `discounts`, `credits`, `credit_notes`, `reminders` sont tous en `cascadeOnDelete()` sur `registration_id`/`user_id`. Un `forceDelete()` futur (ou une requête SQL directe) effacerait silencieusement tout l'historique comptable d'un élève, sans garde-fou applicatif.

---

## 🟡 MOYENNE

- **M1.** `childTimetable()` du portail parent redirige vers `student.timetable`, protégée par `role:eleve` → 403 systématique pour tout parent cliquant sur "Emploi du temps".
- **M2.** `messaging()` du portail parent redirige toujours vers `notifications.index` au lieu d'afficher `parents.messaging.blade.php` (vue existante mais jamais rendue — code mort côté vue, CTA trompeur côté UX).
- **M3.** `GradeController::reopenNotes` vérifie l'autorisation **à l'intérieur** de la boucle `foreach` : si aucune note ne correspond aux filtres, aucun contrôle n'a lieu et un log d'audit "reopened" est quand même écrit — permet aussi de sonder l'existence de notes validées sans être autorisé.
- **M4.** `ProgramAnnualController::importExcel` — création programme + chapitres en boucle sans `DB::transaction` : import partiel possible sans rollback en cas d'erreur.
- **M5.** `PedagogicalConfigurationController::assignments` — pagination remplacée par `take(5)` sans lien vers le reste : affectations au-delà des 5 dernières invisibles (régression fonctionnelle).
- **M6.** `StudentPhotoPolicy` orpheline (jamais enregistrée) ; `LoginLogPolicy` référence 3 permissions (`supprimer-log-connexion`, `restaurer-log-connexion`, `supprimer-definitivement-log-connexion`) jamais définies dans le seeder — bombe à retardement si une route delete est ajoutée.
- **M7.** Permissions du portail parent (`voir-ses-bulletins-enfants`, `voir-messagerie`, `voir-calendrier-scolaire`, `voir-emploi-du-temps`, `voir-ses-discipline-enfants`) absentes de `config/permissions.php` → invisibles et non gérables depuis l'écran d'administration des rôles/permissions.
- **M8.** Cast `decimal:2` manquant sur `Registration::monthly_fee`/`registration_fee_paid` (présent partout ailleurs : Payment, Invoice, ClassroomFee) → sort en chaîne de caractères dans certaines réponses API (`Api\StudentController`), incohérent et source de bugs côté client.
- **M9.** Table pivot `classroom_matiere` créée mais totalement morte (aucun modèle ne l'utilise, remplacée par `pedagogical_assignments`) — source de confusion pour un nouveau développeur.
- **M10.** Duplication `teacher_classroom` (legacy) vs `PedagogicalAssignment` (nouveau) — deux sources de vérité coexistent pour le volume horaire d'un professeur, avec fallback conditionnel fragile.
- **M11.** Incohérence Dark Mode sur `resources/views/parents/children/index.blade.php` (aucune classe `dark:`, contrairement au reste du module).
- **M12.** `$student->full_name` utilisé dans `parents/children/index.blade.php:13` — attribut inexistant sur `User` (le bon attribut est `name`) → nom de l'enfant affiché vide silencieusement.
- **M13.** La sidebar ne se met pas à jour après une navigation PJAX (item actif figé sur l'ancienne page ; `<nav>` hors de la zone `<main>` réinjectée).

---

## 🟢 FAIBLE

- **F1.** 4 listes de rôles dupliquées et incohérentes entre `RoleAssignmentController::SEARCHABLE_ROLES`, `UserController::ROLES/CREATABLE_ROLES`, `StoreUserRequest::ROLES`, `UpdateUserRequest::ROLES` — source structurelle du finding C3, à centraliser.
- **F2.** Code mort : `AttachStudentRequest` jamais utilisé (validation inline à la place), import `Invoice` inutilisé dans `routes/web.php:36`, test `if ($parentProfile)` toujours vrai (query builder) dans `BulletinController.php:36-37`.
- **F3.** Rôle `surveillant` toujours actif dans le seeder et les permissions, mais documenté en commentaire comme "ancienne étape non utilisée" — à trancher (garder ou supprimer proprement).
- **F4.** Écrans du portail parent avec layouts incohérents : `dashboard.blade.php` utilise `<x-app-layout>` + slot header, les 3 autres vues (`calendar`, `messaging`, `children/index`) utilisent `@extends('layouts.app')` + `<h1>` inline sans bandeau d'en-tête standard. `children/index.blade.php` utilise aussi `container mx-auto` (motif Bootstrap, absent du reste du projet) au lieu de `max-w-7xl mx-auto sm:px-6 lg:px-8`.
- **F5.** Boucles d'écriture notes/présences (`GradeController::store/storeForStudent`, `AttendanceController::store`) sans `DB::transaction` englobante — lot partiellement enregistré possible en cas d'échec en cours de boucle.
- **F6.** `config/permissions.php::modules_map` mal rangé par endroits (ex. `voir-sa-classe` classé sous `grades` au lieu de `attendances`) — cosmétique, nuit à la lisibilité de l'écran d'administration.

---

## ✅ Points positifs confirmés (rien à corriger)

- `BulletinController::show()` protège correctement l'accès élève/parent (vérifie explicitement le lien parent-enfant) — c'est le bon modèle à répliquer pour corriger C6.
- Notification `StudentAbsent` correctement scopée aux vrais parents de l'élève, aucune fuite de données.
- Aucun CSRF manquant, aucun XSS (`{!! !!}`), aucune injection SQL brute détectée dans le code audité.
- Retrait de `super-admin` de la liste des rôles créables à la création d'utilisateur (`StoreUserRequest`) — bonne pratique déjà en place (partiellement contredite par C3 côté modification).
- Relations Eloquent, casts et soft deletes cohérents sur tout le périmètre financier (`Payment`, `Invoice`, `InvoiceItem`, `Discount`, `Credit`, `CreditNote`, `ClassroomFee`, `FeeType`).
- Tests `BulletinAccessTest` (3/3) et `GradeEntryByMatriculeTest` (6/6) passent tous — mais aucun des deux ne couvre les scénarios IDOR relevés en C5/C6 (angle mort de couverture, pas un problème de code testé).

---

## Synthèse chiffrée

| Sévérité | Nombre de findings |
|---|---|
| 🔴 Critique | 6 |
| 🟠 Haute | 8 |
| 🟡 Moyenne | 13 |
| 🟢 Faible | 6 |
| **Total** | **33** |

## Recommandation pour la suite

Le Checkpoint 1 est un audit pur : aucune ligne de code n'a été modifiée. Avant de démarrer le **Checkpoint 2 (Architecture)**, je recommande de traiter les 6 findings Critiques dans l'ordre suivant, car plusieurs sont liés (corriger C6 sans traiter C1/C2 en premier laisserait le portail parent inutilisable ; corriger C3 est indépendant et urgent en soi) :

1. C2 (policy inexistante — un seul mot à corriger, débloque tout le module Parents admin)
2. C1 (ordre des routes — réorganisation simple, débloque la navigation du portail parent)
3. C3 (escalade de privilèges — faille de sécurité active dès aujourd'hui)
4. C4 (API élèves non protégée — faille de sécurité active dès aujourd'hui)
5. C5 (IDOR notes par matricule — faille de sécurité active dès aujourd'hui)
6. C6 (IDOR portail parent — à corriger en même temps que C1/C2 puisque c'est la même fonctionnalité)

**Ce rapport doit être validé par vous avant que je démarre le Checkpoint 2.** Dites-moi si vous souhaitez des précisions sur un point, ou si je peux considérer le Checkpoint 1 comme clos et passer à la correction (Checkpoint 2 — Architecture).
