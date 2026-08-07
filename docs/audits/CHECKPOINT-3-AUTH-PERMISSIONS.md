# Checkpoint 3 — Authentification et permissions

**Date :** 2026-08-07
**Périmètre :** Connexion, déconnexion, mot de passe oublié, changement de mot de passe, rôles, permissions, policies, middleware, contrôle par année scolaire / classe / matière. Test des 7 rôles métier (Super Admin, Admin, Comptable, Manager Comptable, Enseignant, Parent, Élève).

---

## 1. Résumé des corrections effectuées

### Critique

**Fuite de données financières sur `/dashboard`**
Tout utilisateur authentifié n'ayant ni le rôle `eleve`, ni `professeur`, ni `parent` tombait dans la branche par défaut de la route `/dashboard`, qui affiche sans aucun contrôle de rôle/permission : chiffre d'affaires mensuel, solde restant dû global, derniers paiements, dernières inscriptions. Un compte sans rôle (voir point suivant) voyait donc les finances de l'établissement.
→ Ajout d'un contrôle explicite `hasAnyRole(['super-admin','admin','manager-comptable','comptable','surveillant'])` avant de construire ces données ; sinon `403`.

**Auto-inscription publique (`/register`) désactivée**
`RegisteredUserController` (scaffolding Laravel Breeze d'origine, jamais retiré) permettait à quiconque sur internet de créer un compte **sans aucun rôle** et d'être connecté immédiatement — ce compte tombait alors exactement dans la fuite décrite ci-dessus. Aucune vue de l'application ne pointait vers `/register` (aucun lien « créer un compte » nulle part), confirmant qu'il s'agissait de code mort dangereux plutôt que d'une fonctionnalité voulue : tous les autres comptes de l'application sont créés par un administrateur avec un rôle assigné (élèves, parents, professeurs, personnel).
→ Route supprimée (`routes/auth.php`), contrôleur et vue orphelins supprimés (`app/Http/Controllers/Auth/RegisteredUserController.php`, `resources/views/auth/register.blade.php`), ainsi que `resources/views/welcome.blade.php` qui n'était rendue par aucune route et référençait elle aussi `route('register')`.
**Si l'auto-inscription était en réalité prévue pour un usage futur (ex. self-signup parent), dites-le-moi — c'est réversible en un commit.**

**IDOR sur la saisie de présences (`AttendanceController::store`, `TeachingSessionController::store`)**
Ni l'un ni l'autre ne vérifiait que les élèves soumis dans le tableau `attendances` étaient réellement inscrits dans la classe visée — un professeur pouvait marquer présent/absent n'importe quel utilisateur du système en modifiant les données du formulaire. Même famille de bug que celui corrigé sur `GradeController` au Checkpoint 2 (C5), non détecté par l'audit initial pour ces deux contrôleurs précis.
→ Ajout d'une vérification d'inscription active dans la classe avant tout enregistrement, dans les deux contrôleurs.

### Vérifications effectuées (aucune anomalie)

- **Connexion / déconnexion** : fonctionne par email ou par matricule, limitation de débit (5 tentatives/minute) opérationnelle, message dédié pour compte désactivé.
- **Mot de passe oublié / réinitialisation** : flux standard Laravel intact, testé par la suite Breeze existante.
- **Changement de mot de passe forcé** (`password_must_change`) : un utilisateur concerné est bien redirigé vers son profil sur toute autre route, et peut toujours accéder au profil et se déconnecter.
- **Vérification d'email** (`verified` middleware) : `User` n'implémente pas `MustVerifyEmail`, donc ce middleware ne bloque jamais personne en pratique — présent sur les routes mais inerte. Ce n'est pas un bug (aucun compte n'est jamais bloqué), juste une configuration silencieuse à connaître ; laissé tel quel car le modifier changerait un comportement fonctionnel (obliger la vérification d'email) sans que ce soit demandé.
- **Contrôle par année scolaire** : déjà couvert par `SchoolYearLockTest` (paiements et tarifs bloqués sur année verrouillée, sauf override super-admin).
- **Contrôle par classe / matière** : vérifié pour `TeacherClassController`, `AttendanceController` (`history`), `TeachingSessionController::index` — tous filtrent correctement par les classes réellement affectées au professeur connecté.

---

## 2. Matrice de permissions — 7 rôles testés

Un test dédié (`tests/Feature/RolePermissionMatrixTest.php`) vérifie, pour chacun des 7 rôles, l'accès à 11 routes représentatives de chaque grand module (dashboard général, admin, utilisateurs, élèves, comptabilité, paiements, rappels, configuration pédagogique, espace professeur, espace élève, espace parent) : **77 combinaisons rôle × route, toutes conformes à la matrice de permissions attendue** (accès accordé aux rôles prévus, `403` pour tous les autres, redirection normale pour les routes qui aiguillent vers un espace dédié).

## 3. Fichiers modifiés

- `routes/web.php` (fuite dashboard corrigée)
- `routes/auth.php` (auto-inscription retirée)
- `app/Http/Controllers/AttendanceController.php` (IDOR présences)
- `app/Http/Controllers/TeachingSessionController.php` (IDOR présences)
- Supprimés : `app/Http/Controllers/Auth/RegisteredUserController.php`, `resources/views/auth/register.blade.php`, `resources/views/welcome.blade.php`

## 4. Tests

- `tests/Feature/Auth/BusinessAuthenticationTest.php` (nouveau) — login par matricule, compte désactivé, redirection `password_must_change`, redirection élève.
- `tests/Feature/Auth/RegistrationTest.php` (réécrit) — confirme que `/register` est fermée.
- `tests/Feature/RolePermissionMatrixTest.php` (nouveau) — 77 assertions, matrice complète des 7 rôles.
- `tests/Feature/AttendanceTest.php` / `tests/Feature/TeachingSessionTest.php` — régression IDOR ajoutée.
- Suite complète : **294 passed (681 assertions), 0 échec.**

---

## Validation

Le Checkpoint 3 est entièrement corrigé et testé : les flux d'authentification fonctionnent pour les 7 rôles, la fuite de données financières et l'auto-inscription dangereuse sont neutralisées, et deux nouvelles failles IDOR sur les présences sont corrigées avec régression zéro.

**En attente de votre validation avant de démarrer le Checkpoint 4 (Module Utilisateurs)** — et de votre confirmation sur la désactivation de `/register`.
