# Phase 1 — Audit complet (nouvelle méthodologie par phases)

**Date :** 2026-08-08
**Périmètre :** Audit en lecture seule de l'intégralité du code applicatif (routes, contrôleurs, modèles, services, policies, migrations, vues Blade, sécurité, UX), sur l'état actuel du dépôt — c'est-à-dire après les 12 checkpoints précédents (`docs/audits/CHECKPOINT-1` à `CHECKPOINT-12`).
**Méthode :** 4 audits indépendants menés en parallèle (routes/contrôleurs/liens morts ; modèles/migrations/services/policies ; UX/vues/duplication ; sécurité/logique métier incrémentale), chacun ayant relu les checkpoints précédents pour ne pas re-signaler ce qui est déjà corrigé — sauf régression. Les findings les plus lourds (marqués **Critique**) ont ensuite été revérifiés personnellement, ligne par ligne, avant d'être inclus ici. **Aucune ligne de code n'a été modifiée dans cette phase.**

---

## Comment lire ce rapport

- **Critique** : crash (500), fonctionnalité totalement inaccessible, ou faille de sécurité exploitable immédiatement.
- **Haute** : faille de sécurité ou bug fonctionnel majeur, touchant un module entier.
- **Moyenne** : bug fonctionnel réel, incohérence notable, dette technique à risque.
- **Faible** : code mort, incohérence cosmétique, dette technique mineure.

Ce rapport ne re-liste pas ce qui a déjà été vérifié comme corrigé (ex. l'ordre des routes du portail parent, l'escalade de privilèges admin→super-admin, la fuite de données `by-matricule`, les 26 vulnérabilités de dépendances, etc. — voir Checkpoints 1, 2, 3 et 11). Il se concentre sur ce qui reste, ce qui a régressé, et ce qui a été introduit depuis.

---

## 🔴 CRITIQUE (4)

### C1. `AnnouncementService::resolveByClassroom()` — classe non importée → crash 500 sur toute annonce ciblée par classe
**Fichier :** `app/Services/AnnouncementService.php:101`
Le fichier n'importe que `Announcement`, `Classroom`, `User` (lignes 5-7) — pas `App\Models\ParentModel`. La ligne 101 utilise pourtant `ParentModel::whereHas(...)` en nom simple, qui résout vers `App\Services\ParentModel`, classe inexistante. **Vérifié directement dans le code.** Le rôle `parent` fait partie de l'ensemble de rôles ciblés par défaut dès qu'une annonce n'exclut pas explicitement ce rôle (comportement par défaut de `resolveRecipients()`).
**Scénario concret :** un admin crée une annonce ciblée sur une classe (`target_mode = 'classroom'`) sans restreindre les rôles — cas d'usage normal. `AnnouncementController::store()`/`publish()` appelle `resolveByClassroom()` → `Error: Class "App\Services\ParentModel" not found` → 500 immédiat. La fonctionnalité "notifier les parents d'une classe" est cassée à 100 %.

### C2. `ProgramAnnualController::importExcel()` — aucune vérification d'affectation prof↔classe, contrairement à `store()`
**Fichier :** `app/Http/Controllers/ProgramAnnualController.php:185-212`
**Vérifié directement dans le code** : `store()` (ligne 82-84) vérifie explicitement `$assignment->teacher->user_id === $request->user()->id` avant de créer un programme. `importExcel()` ne fait que `$this->authorize('create', ProgramAnnual::class)` (permission plate `creer-programme`, accordée à tout le rôle `professeur`) puis crée directement `ProgramAnnual::create(['classroom_id' => $request->input('classroom_id'), 'subject_id' => $request->input('subject_id'), 'teacher_id' => $request->user()->id, ...])` — sans aucune vérification que l'utilisateur enseigne réellement cette classe/matière.
**Scénario concret :** un professeur authentifié envoie une requête `POST /programs/import` avec le `classroom_id`/`subject_id` d'une classe qu'il n'enseigne pas (aucun bouton n'expose cette route dans l'UI actuelle — voir Faible F4 — mais la route existe, accepte sa session standard, et rien ne l'empêche techniquement). Il devient `teacher_id` du programme créé, ce qui lui donne ensuite accès en écriture via `CahierTexteController::toggle/bulkToggle/markLessonDone` (dont l'autorisation repose uniquement sur `$program->teacher_id === auth()->id()`) — il peut alors modifier le cahier de texte d'une classe qui n'est pas la sienne. Contourne complètement le modèle `PedagogicalAssignment` qui protège tout le reste du module.

### C3. Suppression d'une classe → cascade SQL détruit tout l'historique financier et pédagogique, sans garde-fou ni confirmation d'impact
**Fichiers :** `app/Http/Controllers/ClassroomController.php:120-126` (`destroy()`), `app/Policies/ClassroomPolicy.php:58-61` (`delete()`), `app/Models/Classroom.php` (pas de `SoftDeletes`)
**Vérifié directement** : `Classroom` n'a pas de `SoftDeletes` ; `destroy()` appelle `$classroom->delete()` (suppression SQL réelle) après une simple vérification de permission plate (`supprimer-classe`), sans vérifier si des élèves y sont/étaient inscrits. Or `registrations.classroom_id` est en `cascadeOnDelete()` (`database/migrations/2026_06_08_175813_create_registrations_table.php:16`), et `Registration` cascade elle-même vers `payments`, `invoices`, `discounts`, `credits`, `credit_notes`, `reminders` (finding H8 du Checkpoint 1, jamais corrigé — voir Haute H4 ci-dessous). `notes`, `attendances` et `program_annuals` cascadent aussi directement sur `classroom_id`.
**Scénario concret :** un admin supprime une classe ayant eu des élèves inscrits — un seul clic, sans avertissement du périmètre réel — détruit irréversiblement tout l'historique de paiements, factures, notes et présences de tous les élèves qui y sont ou y ont été inscrits. Aucune corbeille, aucune restauration possible.

### C4. `resources/views/parents/show.blade.php` — page structurellement cassée, aucun layout
**Fichier :** `resources/views/parents/show.blade.php` (221 lignes)
**Vérifié directement** : le fichier commence par `<x-slot name="header">` (ligne 1) et se termine par de simples `</div>` (ligne 221) — **aucune balise `<x-app-layout>` ouvrante ni fermante nulle part dans le fichier** (contrairement à `parents/edit.blade.php`, qui a bien les deux). Un `<x-slot>` en dehors du contexte d'un composant est structurellement invalide en Blade. Confirmé comme code committé (pas une modification en cours) via `git log`.
**Impact :** la fiche détaillée d'un parent (`route('parents.show')`, accessible depuis chaque ligne de `parents.index`) s'affiche cassée pour tout admin — au minimum sans mise en page (pas de sidebar, pas de CSS Vite), possiblement une erreur serveur selon la version de Blade. **Non testé en conditions réelles dans cette phase (audit statique uniquement) — à vérifier en priorité au navigateur avant toute correction.**

---

## 🟠 HAUTE (12)

### H1. Workflow de validation des paiements partiels : construit côté serveur, invisible et inaccessible depuis l'interface
**Fichiers :** `app/Http/Controllers/PaymentController.php:226-238` (création — `validated_at` jamais renseigné), `:460-509` (`validationIndex`/`validatePayment`/`rejectPayment`, complets et fonctionnels), `routes/web.php:182-186`, vue existante `resources/views/accounting/payments/validation.blade.php`.
**Vérifié** : `Payment::scopePendingValidation()` = statut `partiel` ET `validated_at` NULL — or `validated_at` n'est jamais défini à la création (`store()`), seulement par `validatePayment()`. Donc **tout paiement partiel** reste indéfiniment "en attente de validation". Aucune vue (`payments/index`, `accounting/dashboard`, `config/sidebar.php`) ne pointe vers `payments.validation` — vérifié par recherche exhaustive. Un pan entier et fonctionnel du module Finance n'a jamais été relié à l'UI.

### H2. Cahier de texte : lecture (`index`) sans aucune autorisation
**Fichier :** `app/Http/Controllers/CahierTexteController.php:22-39`, route protégée seulement par `auth` (`routes/web.php:243`).
N'importe quel utilisateur authentifié — y compris un `eleve` ou un `parent` — peut consulter le contenu du cahier de texte de n'importe quelle classe/matière en manipulant `?classroom_id=X&subject_id=Y`. Contraste avec `toggle`/`bulkToggle`/`markLessonDone`, qui vérifient tous l'affectation. Le re-balayage du Checkpoint 11 ne portait que sur les routes *state-changing* — cette route GET n'avait jamais été auditée.

### H3. `BulletinController::generatePdf`/`generateClassPdf` — aucun scoping par affectation, malgré permission accordée à tout le rôle professeur
**Fichier :** `app/Http/Controllers/BulletinController.php:62-97`
Seule vérification : `$request->user()->can('generer-bulletins')`, permission accordée par défaut à **tout** le rôle `professeur` (`RoleAndPermissionSeeder.php:354`), sans vérifier que le professeur est affecté à l'élève/la classe demandée. Un professeur peut générer le bulletin PDF complet (toutes matières) de n'importe quel élève de l'établissement. Le test existant (`BulletinAccessTest.php:37-44`) documente ce comportement sans le tester par classe — pas un oubli de test, un vrai trou de conception.

### H4. `Registration` toujours sans `SoftDeletes` — finding H8 du Checkpoint 1 non corrigé
**Fichier :** `app/Models/Registration.php`
Le Checkpoint 2 a ajouté les casts `decimal:2` mais pas `SoftDeletes`. Combiné à C3 ci-dessus (suppression de classe), le risque de perte de données financières en cascade reste réel et désormais confirmé atteignable via l'UI (avant, il fallait un `forceDelete()` explicite jamais appelé nulle part ; C3 montre qu'une suppression de classe suffit).

### H5. Upload de pièce jointe d'annonce sans restriction de type — XSS stocké possible
**Fichier :** `app/Http/Controllers/AnnouncementController.php:206`
Règle de validation : `'attachment' => 'nullable|file|max:5120'` — **aucune restriction MIME**, contrairement à tous les autres uploads de l'app (`StudentDocumentController` : `mimes:pdf,jpg,jpeg,png`). Fichier stocké sur le disque `public` (accessible par URL directe). Un SVG/HTML malveillant peut être hébergé sur le domaine propre de l'application.

### H6. Fallback d'upload de photo élève : stockage brut avec extension contrôlée par le client (2 endroits dupliqués)
**Fichiers :** `app/Services/StudentEnrollmentService.php:132-152`, `app/Http/Controllers/StudentController.php:70-99`
Le chemin nominal réencode toujours en JPEG (neutralise le contenu). Mais si `Intervention\Image::make()` lève une exception, le `catch` stocke le fichier **brut**, avec un nom construit depuis `getClientOriginalExtension()` (contrôlé par le client, indépendant du MIME réellement détecté). Défense en profondeur cassée, dupliquée dans 2 fichiers. Exploitabilité dépendante de la config serveur (exécution PHP sous `storage/`), mais c'est une vraie faille de conception.

### H7. Archiver un parent = disparition définitive de l'UI, aucune restauration possible malgré la fonctionnalité existante
**Fichiers :** `app/Http/Controllers/ParentController.php:211-223` (`archive()`, soft-delete), `:index()` (jamais `withTrashed()`), route `parents.restore` fonctionnelle mais jamais liée depuis aucune vue.
Un parent archivé disparaît de toute la liste — **y compris quand on filtre explicitement "Statut : Archivés"**, qui renvoie toujours "Aucun parent trouvé" puisque la requête exclut les soft-deleted par défaut. Le filtre "Archivés" est une fonctionnalité fantôme. Aucun moyen, via l'UI, de récupérer un parent archivé par erreur.

### H8. Workflow de validation/réouverture des notes : zéro interface, même orpheline
**Fichier :** `app/Http/Controllers/GradeController.php:289-334` (`validateNotes()`/`reopenNotes()`, complet, testé au Checkpoint 6), routes `notes.validate`/`notes.reopen` (`role:super-admin|admin`).
Recherche exhaustive dans `resources/views` : aucune référence, aucun bouton, même pas de page orpheline. Un super-admin n'a aucun moyen via l'interface de verrouiller les notes d'une période ou de les rouvrir.

### H9. Incohérences permission-menu vs permission-contrôleur (4 cas) — masquées aujourd'hui, actives dès personnalisation des permissions
**Fichiers :** `config/sidebar.php:50,51,53,54,104,105` vs contrôleurs cibles.
« Factures » (menu : `voir-factures`) → `InvoiceController::index()` exige `voir-comptabilite`. « Grille tarifaire »/« Types de Frais » (menu : `voir-types-frais`) → `FeeTypeController::index()` exige `voir-comptabilite`. « Impayés & Recouvrement » (menu : `voir-recouvrement`) → `AccountingController::alerts()` exige `voir-alertes-impayes`. « Analyse avancée » (menu : `voir-rapports-avances`) → le contrôleur exige `voir-rapports-financiers` (probable régression de la fusion du Checkpoint 12). Actuellement invisible car ces permissions sont accordées en bloc par rôle dans le seeder — mais l'app expose un écran de personnalisation fine des permissions par utilisateur (`UserPermissionOverride`) qui rendrait ces incohérences immédiatement visibles (lien affiché → 403, ou l'inverse), reproduisant exactement le pattern H1 du Checkpoint 1.

### H10. Aucune protection anti double-soumission sur l'enregistrement d'un paiement
**Fichier :** `resources/views/accounting/payments/create.blade.php:223,478-481`
Le bouton "Confirmer et enregistrer" est un `type="button" onclick="submitPaymentForm()"` qui soumet directement le formulaire sans se désactiver ni afficher de chargement. Un double-clic peut créer deux paiements pour une même opération — sur une action financière.

### H11. Bibliothèque de composants UI standardisés quasi inutilisée — duplication massive et déjà en contradiction avec l'objectif de cohérence graphique
**Fichiers :** `resources/views/components/badge.blade.php`, `card.blade.php`, `primary-button.blade.php`, `secondary-button.blade.php`, `danger-button.blade.php`.
`<x-badge>` (commenté "standardisé pour tous les statuts") : utilisé dans seulement 2 vues sur toute l'app ; ailleurs, badges de statut réimplémentés à la main avec au moins 9 variantes de classes différentes pour le même sens. `<x-card>` (commenté "remplace le pattern répété dans des dizaines de vues") : utilisé dans **1 seule vue** ; les 6 tableaux de bord de rôles différents réimplémentent chacun leur carte avec des styles incohérents (`gray-800` vs `slate-800` utilisés de façon interchangeable). `<x-primary-button>` : utilisé seulement dans le module `auth/*` (5 fichiers), 60 fichiers ont `bg-indigo-600` en dur avec 30+ combinaisons de classes différentes. `<x-secondary-button>`/`<x-danger-button>` : **0 utilisation** dans toute l'application — composants entièrement morts. C'est une dette directement pertinente pour la Phase 4 (Interface utilisateur) prévue dans la méthodologie.

### H12. Injection de formule Excel (CWE-1236) dans l'export comptable
**Fichier :** `app/Http/Controllers/AccountingController.php:255-256` (`exportAdvancedReports`)
`$payment->registration->user->name` et `->classroom?->name` sont écrits via `setCellValue()` sans échappement des caractères `=`, `+`, `-`, `@`. **Vérifié** : la validation du nom/prénom d'un élève (`UpdateStudentRequest.php:24-25`, `RegistrationController.php:44-45`) n'impose que `string|max:255`, aucune restriction de caractères. Un nom d'élève du type `=CMD|'/C calc'!A0` finit tel quel dans chaque export `.xlsx`, avec risque d'exécution à l'ouverture si les protections Excel (mode protégé/DDE) sont désactivées côté personnel comptable.

---

## 🟡 MOYENNE (16)

- **M1.** `AccountingController::index`, tableau `$monthlyRevenue` (ligne ~54-63) : filtre `status = 'complet'` uniquement, sans `'partiel'`, contrairement aux cartes `$stats['monthly_revenue']`/`yearly_revenue'` juste au-dessus sur la même page — exactement le bug corrigé au Checkpoint 12 dans `filteredPaymentsQuery()`, mais réapparu ici car ce bloc n'a pas été unifié.
- **M2.** Incohérence de champ de date pour le "revenu du mois" : `AccountingController::index`/le dashboard filtrent sur `created_at`, tandis que `cashFlow()` et `filteredPaymentsQuery()` (utilisée par Analyse avancée/export) filtrent sur `payment_date` (saisi librement, potentiellement antidaté). Le chiffre peut diverger entre `/dashboard`, `/accounting` et `/accounting/cash-flow`.
- **M3.** `ProgramAnnualController::importExcel()` (voir C2) omet aussi `school_year_id`, `pedagogical_assignment_id`, `academic_period_id` que `store()` renseigne systématiquement — programmes orphelins/mal classés dans les filtres par année.
- **M4.** `PaymentService::logAction()` et `AuditLogService::log()` — logique de journalisation d'audit dupliquée à l'identique dans 2 fichiers (même 9 colonnes, même requête `DB::table('audit_logs')->insert()`), utilisées séparément par `PaymentController` et `GradeController`/`StudentStatusService`.
- **M5.** `ParentPolicy` — 9 méthodes (`view`, `update`, `delete`, etc.) jamais atteintes par les appels `authorize()` du contrôleur (mismatch kebab-case → camelCase, même bug que C6 du Checkpoint 1, jamais corrigé sur cette policy précise). Impact limité aujourd'hui (routes réservées à `role:super-admin|admin`), mais logique métier `$user->id === $parent->user_id` du code mort.
- **M6.** `EvaluationType::default_coefficient`/`default_scale` configurables dans l'UI d'administration mais jamais lus par `GradeCalculationService` (moyenne = simple `avg('valeur')`, seul `Matiere::coefficient` compte réellement) — champ de configuration sans aucun effet, trompeur.
- **M7.** `Teacher::setRibAttribute()` utilise `bcrypt()` (hachage à sens unique, irréversible) pour le RIB — donnée normalement destinée à être réutilisée pour un virement de salaire, jamais un usage de mot de passe.
- **M8.** `CahierTexteService::computeProgress()` retourne toujours `volume_realise`/`volume_prevu` à 0 (jamais calculés), alors que `ProgramProgressService::metrics()` calcule un équivalent complet et correct — deux services pour la même notion de progression, un cassé.
- **M9.** `authorize()`/`Gate::authorize()` appelés avec une instance de modèle sans Policy enregistrée (`ClassroomFee`, `FeeType`, `Invoice`, `SchoolYear`) — l'instance passée est silencieusement ignorée, seule la permission nommée compte ; trompeur pour un futur développeur.
- **M10.** Gestion multi-enseignants par classe (`classrooms.teachers`, `attachTeacher`/`detachTeacher`) : fonctionnalité complète côté serveur avec vue dédiée, mais aucun lien depuis `classrooms/index.blade.php` — orpheline.
- **M11.** 3 routes de démo (`/export/pdf-hello-world`, `/export/pdf-preview`, `/export/excel-hello-world`) déclarées avant le groupe `auth` (`routes/web.php:51-53`) — accessibles sans authentification, génération PDF/Excel illimitée côté serveur (surface d'abus mineure).
- **M12.** Bouton "Prévisualiser" d'une annonce (`AnnouncementController::store` avec `action=preview`) persiste réellement un brouillon en base **avant** toute confirmation — fermer l'onglet laisse un brouillon orphelin ; nom trompeur.
- **M13.** `resources/views/programs/edit.blade.php` et `cahier-textes/select.blade.php` : quasi aucune classe `dark:`/style minimaliste, contrastant fortement avec les autres vues du même module (déjà corrigées au Checkpoint 9) — angle mort du balayage par regex (`bg-white`/`bg-gray-*`) qui ne détecte pas les pages sans ces classes du tout.
- **M14.** Badges de statut de présence (`attendances/overview.blade.php:14`, `teachers/attendances/history.blade.php:40`) sans variante `dark:`, alors que le tableau environnant en a — détonnent en mode sombre.
- **M15.** `cahier-textes/dashboard.blade.php:36` : tableau à 7 colonnes sans wrapper `overflow-x-auto`, contrairement à 16+ autres tableaux de l'app — risque de débordement mobile.
- **M16.** Boutons de génération PDF/Excel sans état désactivé sur opérations lourdes : `bulletins/index.blade.php` (3 boutons T1/T2/T3, génération PDF par classe), `accounting/advanced-reports.blade.php` (export Excel) — clics multiples possibles, générations concurrentes côté serveur.

---

## 🟢 FAIBLE (13)

- **F1.** `students.remove-photo` (route + méthode) jamais utilisée — la suppression de photo passe entièrement par une case à cocher du formulaire principal. Code mort.
- **F2.** `announcements.destroy` orpheline — seule "Archiver" est exposée dans l'UI.
- **F3.** `login-logs.show` orpheline — pas de lien "Détails" depuis la liste, bien que la vue fonctionne.
- **F4.** `programs.import`/`programs.template` (voir C2/M3) jamais reliées à aucune vue — la fonctionnalité dangereuse identifiée en C2 n'est aujourd'hui exploitable que par appel HTTP direct, pas par navigation normale.
- **F5.** `parents.destroy` (suppression définitive + compte utilisateur associé) orpheline — probablement un choix délibéré (seul "Archiver" exposé), mais reste accessible en la devinant.
- **F6.** `TeacherPolicy::manageRib()` jamais invoquée ; `TeacherController::authorizeCanViewRib()` réimplémente la même règle indépendamment — duplication mineure.
- **F7.** Gate `remove-photo-eleve` pointe vers la même permission que `upload-photo-eleve` (pas de permission dédiée) — nom trompeur, sans doute volontaire.
- **F8.** `LoginLogPolicy` référence toujours 3 permissions absentes du seeder (`supprimer-log-connexion`, etc.) — signalé au Checkpoint 1, confirmé sans danger (Spatie renvoie `false` proprement), persiste tel quel.
- **F9.** `AccountingController::index`, `total_payments` compte tous les paiements non annulés (y compris rejetés), incohérent avec les autres métriques de revenu de la même page filtrées à `complet`+`partiel` — possiblement volontaire (compter les tentatives), à trancher avec vous.
- **F10.** `<x-modal>` : toujours 0 utilisation dans toute l'app (signalé "en attente de décision" au Checkpoint 9, toujours non tranché).
- **F11.** `<x-secondary-button>`/`<x-danger-button>` : 0 utilisation, composants morts (cf. H11).
- **F12.** `resources/views/components/danger-button.blade.php`/`input-error.blade.php` sans classes `dark:`, contrairement aux composants voisins — impact visuel mineur.
- **F13.** Module `parents` : incohérence de layout toujours présente (`x-app-layout` vs `@extends('layouts.app')`) et `children/index.blade.php` toujours sans aucune classe `dark:` — findings F4/M11 du Checkpoint 1, jamais corrigés malgré le passage dédié du Checkpoint 9.

---

## ✅ Points positifs confirmés (rien à corriger)

- **215 routes** vérifiées programmatiquement (réflexion PHP) : toutes pointent vers une classe/méthode de contrôleur qui existe réellement — aucun risque de 500 par route mal câblée.
- **165 appels `route(...)`** dans les vues, comparés aux 209 routes nommées : **zéro référence à une route inexistante**.
- **66 migrations**, toutes `Ran` — aucune en attente (H5 du Checkpoint 1 reste corrigé).
- Aucun `::create($request->all())`/`->fill($request->all())`/`->update($request->all())` dans aucun contrôleur — mass assignment toujours maîtrisé.
- Aucun `href="#"`, `javascript:void(0)`, `@csrf` manquant, ou déséquilibre `@if`/`@endif` détecté sur les 121 vues.
- `FeeService` reste la source de vérité unique pour la situation financière ; `ReminderService` (Checkpoint 12) correctement aligné dessus.
- `AddSecurityHeaders`, `throttle:api`, en-têtes de sécurité : toujours actifs, aucune régression.
- `GradeController`, `AttendanceController`, `TeachingSessionController`, `TeacherClassController`, `ParentPortalController`, `StudentDocumentController` : toujours conformes aux correctifs des Checkpoints 1/11 (vérification d'affectation/appartenance correcte, pas de régression IDOR).
- Correctifs C1-C6 et H1-H8 du Checkpoint 1 (portail parent, escalade de privilèges, IDOR API élèves, IDOR notes par matricule) : tous revérifiés dans le code actuel, aucune régression.

---

## Synthèse chiffrée

| Sévérité | Nombre de findings |
|---|---|
| 🔴 Critique | 4 |
| 🟠 Haute | 12 |
| 🟡 Moyenne | 16 |
| 🟢 Faible | 13 |
| **Total** | **45** |

## Analyse de ce que révèle ce rapport

Un motif se dégage nettement de cet audit, différent de celui du Checkpoint 1 (qui portait surtout sur des failles d'autorisation actives) : **une part importante des findings de cette phase sont des fonctionnalités complètes côté serveur, testées ou non, mais jamais reliées à l'interface** (H1, H7, H8, H10/M10, F1-F5). Le "backend" du projet est souvent en avance sur son "frontend" — ce qui suggère que la Phase 3 (Navigation/UX) et la Phase 4 (Interface) de votre méthodologie seront particulièrement chargées. À l'inverse, les vrais trous de sécurité restants (C1, C2, H2, H3, H5, H6, H12) sont plus localisés et concentrés sur des modules ajoutés ou modifiés après le Checkpoint 11 (Pédagogie/Cahier de texte, Annonces, Comptabilité).

## Recommandation pour la suite

Avant de démarrer la **Phase 2 (Architecture)**, je recommande de traiter les 4 findings Critiques dans cet ordre :

1. **C1** (import manquant — une ligne à corriger, débloque la fonctionnalité d'annonces par classe)
2. **C4** (layout manquant sur la fiche parent — à vérifier au navigateur en premier, correction probablement triviale une fois confirmée)
3. **C2** (import de programme sans vérification d'affectation — faille de sécurité, corrigeable en répliquant la vérification déjà présente dans `store()`)
4. **C3** (cascade de suppression de classe — nécessite une décision produit : bloquer la suppression si des inscriptions existent ? passer `Classroom`/`Registration` en `SoftDeletes` ? Les deux ?)

**Ce rapport doit être validé par vous avant que je démarre la Phase 2 (Architecture).** Dites-moi si vous voulez des précisions sur un point, si vous voulez que je hiérarchise différemment, ou si je peux considérer la Phase 1 comme close.
