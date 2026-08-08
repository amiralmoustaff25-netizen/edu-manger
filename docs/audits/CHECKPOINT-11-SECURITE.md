# Checkpoint 11 — Sécurité

**Date :** 2026-08-08

---

## 1. Dépendances vulnérables

`composer audit` a signalé **26 avis de sécurité sur 5 paquets**. Après mise à jour (dans les contraintes déjà définies par `composer.json` — aucun changement de version majeure requise) :

| Paquet | Avant | Après | Avis corrigés |
|---|---|---|---|
| `dompdf/dompdf` | v3.1.5 | v3.1.6 | 6 (fuite de fichiers via SVG, déni de service par images surdimensionnées) |
| `guzzlehttp/guzzle` + `psr7` + `promises` | 7.11.1 / 2.11.0 | dernières versions compatibles | 11 |
| `league/commonmark` | 2.8.2 | ≥2.9.0 | 6 (déni de service par Markdown malveillant) |

**Restent non corrigés : 3 avis `phpoffice/phpspreadsheet`** (haute sévérité — épuisement mémoire via fichiers XLS/Gnumeric malveillants, contournement SSRF de la fonction `WEBSERVICE()`). La version corrigée exige l'extension PHP `gd`, absente de cet environnement — l'activer nécessite de modifier `php.ini`, une configuration système hors du dépôt que je n'ai pas modifiée sans votre accord. **Risque réel limité** : recherche exhaustive confirmée — l'application n'utilise PhpSpreadsheet que pour **écrire** des exports Excel, jamais pour lire des fichiers importés par un utilisateur ; les trois failles nécessitent de faire lire un fichier malveillant à l'application, ce qui ne se produit jamais dans le code actuel. **Action recommandée avant mise en production** : activer `gd` puis relancer `composer update phpoffice/phpspreadsheet`.

Côté JavaScript, `npm audit` a trouvé 8 vulnérabilités, toutes dans des **dépendances de build** (Vite, PostCSS, form-data...), jamais expédiées au navigateur (le seul paquet en `dependencies` réel est `apexcharts`, chargé en lazy-loading). `npm audit fix` a corrigé 6 des 8 sans rien casser (build re-testé avec succès). La dernière (`esbuild`, sévérité modérée, expose le serveur de dev local — pas la production) nécessite une montée de version majeure de Vite (5→8) risquant de casser la configuration de build ; laissée de côté pour éviter une régression non testée, à traiter dans un chantier dédié si souhaité.

## 2. Mass assignment — vérifié, aucune faille réelle

Tous les modèles ont `$fillable` ou `$guarded`. Le modèle `User` autorise en mass assignment des champs sensibles (`role`, `is_active`, `password_must_change`) — à première vue risqué, mais vérification exhaustive de **tous** les points d'écriture (`UserController`, `RoleAssignmentController`, `ParentController`, `PaymentController`, `StudentEnrollmentService`...) : aucun ne transmet `$request->all()` ou des données brutes non filtrées à ces champs. Chaque écriture passe soit par un tableau littéral construit côté serveur, soit par un Form Request dont `validated()` n'expose que les champs explicitement autorisés (ex. `role` validé par `Rule::in(UserRoles::assignableBy(...))`, empêchant toute élévation de privilège). Retirer ces champs de `$fillable` casserait des fonctionnalités légitimes (`RoleAssignmentController::update(['role' => ...])`, activation/désactivation de comptes) sans fermer de faille réelle — aucun changement effectué.

## 3. Upload/téléchargement de documents élève — déjà sécurisé

`StudentDocumentController` : type de fichier validé (`mimes:pdf,jpg,jpeg,png`), taille limitée à 5 Mo, stockage sur le disque `local` (`storage/app/private`, **hors du webroot public** — impossible d'accéder à un document par URL directe), téléchargement protégé par autorisation ET vérification IDOR (`$document->user_id === $student->id`). Aucune faille trouvée.

## 4. Injection SQL — vérifié, aucune faille

Une seule requête `DB::raw()` dans toute l'application (`AccountingController`, agrégats `COUNT(*)`/`SUM(amount)`), sans aucune donnée utilisateur interpolée. Aucun `whereRaw`/`selectRaw` ailleurs. Le reste de l'application utilise systématiquement le query builder Eloquent (requêtes préparées).

## 5. Code mort trouvé et nettoyé : limiteurs de débit jamais appliqués

`RateLimiter::for('login', ...)` était défini **deux fois** (`bootstrap/app.php` et `AuthServiceProvider`), et `RateLimiter::for('api', ...)` une fois — mais **aucun des deux n'était réellement utilisé** : la connexion utilise le mécanisme intégré de Laravel Breeze (`LoginRequest::ensureIsNotRateLimited()`, fonctionnel, 5 tentatives/minute) sans passer par le limiteur nommé, et aucune route ne portait `throttle:api`.

**Corrigé :**
- Suppression de la définition dupliquée et inutilisée dans `bootstrap/app.php`.
- Le limiteur `'api'` est maintenant réellement appliqué (`throttle:api`) sur les deux routes API de recherche élève par matricule (`/api/students/by-matricule/{matricule}`, `/api/students/{registrationId}/fees`) — ces routes restent accessibles à tout le personnel comptable, mais un compte compromis ou un script ne peut plus les interroger en boucle sans limite.

## 6. En-têtes de sécurité HTTP — absents, ajoutés

Aucun en-tête de sécurité n'était présent sur les réponses (vérifié directement : `curl -I` ne renvoyait que les en-têtes par défaut de Laravel/PHP). **Ajouté** un middleware global (`AddSecurityHeaders`) qui ajoute `X-Frame-Options: SAMEORIGIN` (protection contre le clickjacking), `X-Content-Type-Options: nosniff` et `Referrer-Policy: strict-origin-when-cross-origin` à toutes les réponses. Une politique CSP (Content-Security-Policy) plus stricte n'a volontairement pas été ajoutée : mal configurée, elle casse silencieusement les scripts inline utilisés dans toute l'application (dont le correctif PJAX du Checkpoint 9) — un chantier à part nécessitant des tests dédiés si souhaité.

**Observations pour le déploiement en production (non modifiées ici, dépendent de l'environnement) :**
- `SESSION_SECURE_COOKIE` n'est pas défini : correct en local (HTTP), **doit être mis à `true`** dès que l'application tourne en HTTPS, sinon le cookie de session continuerait de circuler en clair.
- `X-Powered-By: PHP/8.2.12` fuit la version de PHP dans chaque réponse — se corrige via `expose_php = Off` dans `php.ini`, une configuration serveur hors du dépôt.

## 7. Exposition de données sensibles — une vraie fuite trouvée et corrigée

`GET /api/students/by-matricule/{matricule}` (recherche élève avant saisie de paiement, réservée au personnel comptable) renvoyait le **modèle `User` complet** de l'élève — y compris `medical_notes`, `emergency_contact_phone`, `adresse`, `date_naissance` — ainsi que la liste complète de ses **parents** et de tous ses **paiements**, alors que `resources/views/accounting/payments/create.blade.php` (seule consommatrice de cette API) n'utilise que le nom de l'élève, le nom de sa classe et l'année scolaire. Le personnel comptable n'a aucune raison de recevoir les notes médicales ou les coordonnées d'urgence d'un élève pour saisir un paiement.

**Corrigé** : la réponse ne renvoie plus que les champs réellement consommés (`registration_id`, `matricule`, `user.name`, `classroom.name`, `school_year.year_string`, `situation` financière). Les mots de passe/`remember_token` n'étaient de toute façon jamais exposés (protégés par `$hidden` sur le modèle `User`, qui s'applique automatiquement à toute sérialisation JSON).

Vérification systématique des 4 autres contrôleurs renvoyant du JSON (`PaymentController`, `CahierTexteController`, `CahierTexteDashboardController`, `UserNotificationController`) : tous construisent déjà des tableaux minimaux explicites, aucune autre fuite trouvée.

## 8. Re-balayage de l'autorisation sur toutes les routes

96 routes state-changing (POST/PUT/PATCH/DELETE) recensées. 16 d'entre elles n'ont pas de middleware `role:`/`permission:` au niveau route — vérification individuelle de chacune : toutes protègent l'action via `$this->authorize()` ou `Gate::authorize()` en tout début de méthode (y compris `CahierTexteController::markLessonDone`, qui ajoute même une vérification explicite que l'enseignant agit uniquement sur ses propres cours). Aucune lacune trouvée — les corrections des Checkpoints 1 à 3 (contrôle d'accès, IDOR, escalade de privilèges) tiennent toujours après tous les changements des checkpoints suivants.

---

## 9. Fichiers modifiés

**Dépendances :** `composer.lock`, `package-lock.json`.

**Nettoyage / limiteurs de débit :** `bootstrap/app.php`, `routes/web.php`.

**En-têtes de sécurité :** `app/Http/Middleware/AddSecurityHeaders.php` (nouveau), `bootstrap/app.php`.

**Fuite de données :** `app/Http/Controllers/Api/StudentController.php`.

**Tests :** `tests/Feature/PaymentApiTest.php` (nouveau test de non-régression sur la minimisation des données).

## 10. Tests

- Suite complète relancée après chaque changement de dépendance (dompdf, PDF/bulletins testés spécifiquement).
- `tests/Feature/PaymentApiTest.php` : nouveau test vérifiant explicitement l'absence de `medical_notes`, `emergency_contact_phone`, `adresse`, `parents` et `payments` dans la réponse de recherche par matricule.
- Suite complète : voir validation ci-dessous.

---

## Limites de ce checkpoint

Trois points identifiés nécessitent une action **hors du dépôt de code**, documentée mais non exécutée sans votre validation explicite : activer l'extension PHP `gd` (pour finir la mise à jour de PhpSpreadsheet), activer `SESSION_SECURE_COOKIE=true` au moment du déploiement HTTPS, désactiver `expose_php` dans `php.ini`. Une politique CSP complète est également laissée de côté — les en-têtes ajoutés ici couvrent le clickjacking et le MIME-sniffing, les risques les plus simples à corriger sans casser l'existant.

## Validation

Le Checkpoint 11 est corrigé et testé. Une vraie fuite de données personnelles (dossier médical, contact d'urgence, adresse, liste des parents d'un élève exposée au personnel comptable) a été trouvée et corrigée, ainsi que 26 vulnérabilités de dépendances (23 corrigées, 3 documentées avec mitigation), du code mort autour du rate limiting, et l'absence totale d'en-têtes de sécurité HTTP. Le re-balayage de l'autorisation sur les 96 routes state-changing confirme qu'aucune régression n'a été introduite par les dix checkpoints précédents.

**En attente de votre validation avant de démarrer le Checkpoint 12 (Validation finale).**
