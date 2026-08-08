# Checkpoint 10 — Optimisation

**Date :** 2026-08-07

---

## 1. Requêtes N+1 corrigées

Audit systématique des contrôleurs qui renvoient des listes, croisé avec ce que les vues Blade consomment réellement en boucle. Trois cas confirmés :

- **`ParentController::index`** (`resources/views/parents/index.blade.php:69`) : la vue affiche `$parent->students->count()` sans que `students` soit chargé à l'avance — une requête supplémentaire par parent affiché. **Corrigé** avec `->withCount('students')` (moins coûteux qu'un eager-load complet puisque seul le nombre est affiché).
- **`ClassroomController::index`** (`resources/views/classrooms/index.blade.php:51`) : la vue affiche `$classroom->teacher->name`, relation non chargée — une requête supplémentaire par classe. **Corrigé** avec `->with('teacher')`.
- **`ProgramAnnual::getProgressPercentage()`** (accesseur du modèle) : recalculait la somme des volumes horaires via une requête `$this->chapters()->sum(...)` à chaque appel plutôt que d'utiliser la relation déjà chargée. **Corrigé** : utilise `$this->chapters` (collection déjà chargée) quand disponible, retombe sur la requête sinon ; `ProgramAnnualController::index` charge maintenant `chapters` en plus des relations déjà présentes.

**Vérifié par deux nouveaux tests de régression** (`tests/Feature/QueryPerformanceTest.php`) qui mesurent le nombre réel de requêtes SQL exécutées sur `/parents` et `/classrooms` avec 1 ligne puis 10 lignes : le nombre de requêtes reste strictement identique dans les deux cas, prouvant l'absence de N+1 et empêchant une régression future.

Aucune autre page à fort trafic (élèves, utilisateurs, professeurs, paiements, factures, logs de connexion, notifications, bulletins, tableau de bord) n'a révélé de problème — toutes chargent déjà correctement leurs relations.

## 2. Index de base de données manquants

SQLite (moteur utilisé par cette application) n'indexe **pas automatiquement** les colonnes de clé étrangère, contrairement à MySQL/InnoDB — une différence facile à ignorer puisque le code Laravel (`->constrained()`) est identique dans les deux cas. Une première passe d'indexation existait déjà (migration `2026_07_13_103344`, tables à fort trafic : utilisateurs, inscriptions, classes, paiements, notes, présences, parents, années scolaires). Le balayage de cette session a trouvé 15 tables supplémentaires sans index sur leurs clés étrangères, notamment :

- **`login_logs`** (déjà 130 lignes, filtrée par `user_id`, `status` et `login_at` dans `LoginLogController::index` — aucun des trois indexé).
- Tables financières annexes : `invoices`, `invoice_items`, `discounts`, `credits`, `credit_notes`, `reminders` (toutes interrogées via des relations `hasMany` depuis `registration_id`/`payment_id`).
- `audit_logs.user_id` (journal d'audit réellement utilisé — `AuditLogService`, appelé depuis plusieurs contrôleurs — et donc appelé à grossir en continu).
- `announcements.classroom_id`, `program_chapters.program_annual_id`, `chapter_completions.program_chapter_id`, `teaching_sessions.pedagogical_assignment_id`, `student_documents.user_id`, `user_permission_overrides.user_id`, `user_role_history.user_id`.

**Corrigé** : nouvelle migration `2026_08_07_202750_add_more_reporting_indexes_for_optimization.php`, appliquée avec succès.

## 3. Configuration cache / optimisation Laravel

- `config:cache` et `route:cache` testés : fonctionnent sans erreur (garantit qu'un futur déploiement en production pourra les utiliser). Non laissés actifs en local — `APP_ENV=local` doit refléter les changements de code immédiatement, les activer en permanence casserait le confort de développement.
- Vérifié : la recherche de l'année scolaire active (`SchoolYear::where('is_active', true)->first()`) est appelée une seule fois par requête à chaque endroit, sur une table de quelques lignes déjà indexée sur `is_active` — mettre ce résultat en cache applicatif n'apporterait aucun gain mesurable au volume de données actuel ou réaliste, et ajouterait un risque d'invalidation oubliée. Pas de changement — over-engineering évité.
- **Corrigé un vrai problème trouvé au passage** : `storage/logs/laravel.log` grossissait sans jamais tourner (7,3 Mo déjà en développement), car `LOG_CHANNEL=stack` pointait par défaut vers le canal `single` (un seul fichier, jamais purgé). Changé en `LOG_CHANNEL=daily` (rotation quotidienne native de Laravel, conservation 14 jours) dans `.env` et `.env.example`. Vérifié fonctionnel (nouveau fichier daté créé, ancien fichier conservé tel quel).

## 4. Assets front (JS/CSS)

Déjà bien optimisés, aucune correction nécessaire :
- Dépendance JS unique (`apexcharts`), déjà chargée en **lazy-loading** via `import()` dynamique (`resources/js/charts/payments-chart.js`) — le gros paquet (622 Ko) ne se télécharge que sur le tableau de bord, jamais sur les autres pages.
- Bundle principal léger (91 Ko / 34 Ko gzippé), noms de fichiers hashés par Vite (cache navigateur + invalidation automatique au redéploiement déjà correctement configurés par défaut).

## 5. Bug réel signalé en direct par l'utilisateur pendant ce checkpoint

En cours de travail, vous avez signalé que toutes les pages restaient bloquées sur « Mon Profil ». Ce n'était **pas** lié aux optimisations en cours, mais un bug préexistant révélé par le fait que votre compte a le drapeau « mot de passe à changer » activé.

**Cause réelle :** le middleware `EnsurePasswordChanged` (comportement de sécurité intentionnel) redirige effectivement toute page vers `Mon Profil` tant que le mot de passe n'a pas été changé, avec un message d'avertissement expliquant pourquoi. Mais ce message n'était **jamais visible**, pour deux raisons cumulées :

1. Le système de notifications (toasts) ne savait afficher que les messages de succès/erreur, jamais les avertissements (`session('warning')` silencieusement ignoré).
2. Une fois ce premier point corrigé, un problème d'ordonnancement JavaScript empêchait quand même l'affichage après un clic dans le menu (navigation PJAX) : le script qui déclenche le toast s'exécutait avant qu'Alpine.js n'ait fini de démarrer et d'attacher son écouteur d'événement.

**Corrigé :**
- `resources/views/layouts/app.blade.php` : les toasts gèrent maintenant aussi les messages `warning` (fond ambre), et sont déclenchés par un événement JS différé pour garantir qu'Alpine.js est prêt, aussi bien au premier chargement qu'après une navigation PJAX.
- `resources/js/pjax.js` : lorsque le serveur redirige ailleurs que l'URL cliquée (comme ici), la barre d'adresse reflète maintenant la destination réelle (`response.url`) au lieu de l'URL cliquée à l'origine — évite une incohérence trompeuse entre l'URL affichée et le contenu réellement montré.

**Vérifié en reproduisant exactement votre situation** (compte avec le drapeau activé, clic sur un lien du menu) : le message « Vous devez changer votre mot de passe avant de continuer. » s'affiche désormais correctement, et l'URL affichée correspond bien à la page réellement montrée.

**Pour débloquer votre compte** : sur Mon Profil, « Changer le mot de passe » puis en définir un nouveau — la navigation redeviendra normale immédiatement après.

---

## 6. Fichiers modifiés

**Requêtes N+1 :**
`app/Http/Controllers/ParentController.php`, `app/Http/Controllers/ClassroomController.php`, `app/Http/Controllers/ProgramAnnualController.php`, `app/Models/ProgramAnnual.php`, `resources/views/parents/index.blade.php`.

**Index base de données :**
`database/migrations/2026_08_07_202750_add_more_reporting_indexes_for_optimization.php` (nouveau).

**Configuration :**
`.env`, `.env.example` (rotation des logs).

**Bug navigation/notifications (signalé en direct) :**
`resources/views/layouts/app.blade.php`, `resources/js/pjax.js`.

**Build :** `public/build/*` régénéré.

## 7. Tests

- `tests/Feature/QueryPerformanceTest.php` (nouveau, 2 tests — régression N+1 sur `/parents` et `/classrooms`).
- Suite complète : **340 passed (832 assertions), 0 échec** (338 tests hérités + 2 nouveaux).
- Correction du bug de navigation vérifiée manuellement dans le navigateur en reproduisant exactement le scénario signalé (compte avec `password_must_change = true`, clic dans le menu, vérification du message affiché et de l'URL).

---

## Validation

Le Checkpoint 10 est corrigé et testé. Au-delà du travail d'optimisation prévu (requêtes N+1, index, rotation des logs — assets et cache déjà sains), un bug fonctionnel réel a été trouvé et corrigé grâce à votre remontée en direct : un message d'avertissement de sécurité important restait invisible, ce qui rendait la navigation incompréhensible pour tout compte avec un changement de mot de passe en attente.

**En attente de votre validation avant de démarrer le Checkpoint 11 (Sécurité).**
