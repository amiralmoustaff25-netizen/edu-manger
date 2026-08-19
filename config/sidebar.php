<?php

return [
    'items' => [
        // Admin / Super-Admin common
        [
            'label' => 'Tableau de bord',
            'route' => 'dashboard',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'permission' => 'voir-dashboard',
            // professeur/parent/eleve ont chacun leur propre entrée "Tableau de bord" (ou
            // "Accueil") dédiée plus bas, qui pointe vers la même page — sans cette
            // exclusion, ce lien générique apparaît en double dans leur menu.
            'exclude_roles' => ['professeur', 'parent', 'eleve'],
        ],
        [
            // Sitemap complet de tous les modules accessibles (AdminController::index), pas
            // un doublon du Tableau de bord (KPIs) : déplacé hors du groupe Administration
            // (où il était noyé comme simple 1er sous-item) pour rester visible juste après
            // le Tableau de bord.
            'label' => 'Vue d\'ensemble',
            'route' => 'admin.dashboard',
            'icon' => 'M4 6a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V6zM14 6a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V6zM4 16a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 16a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z',
            'roles' => ['super-admin', 'admin'],
            'active_routes' => ['admin.dashboard'],
        ],
        // --- Regroupement simplifié (5 macro-groupes) pour Super-Admin / Admin ---
        [
            'label' => 'École',
            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            // Pas de 'roles' ici : le groupe se base uniquement sur les permissions de ses
            // enfants (chacun a déjà son propre 'permission'), pour qu'un rôle personnalisé
            // avec la bonne permission voie le groupe sans devoir s'appeler admin/surveillant
            // (correctif RBAC validé le 2026-08-09 — un 'roles' codé en dur, même élargi,
            // reste fragile face à tout futur rôle métier personnalisé).
            // exclude_roles : gestion des inscriptions/promotions/dossiers élèves = travail
            // administratif, pas celui d'un professeur — même si un compte professeur se
            // retrouve un jour avec une permission de ce groupe (ex. voir-eleves accordée
            // individuellement), ce groupe ne doit jamais lui apparaître.
            'exclude_roles' => ['professeur'],
            'active_routes' => ['students.*', 'registrations.*', 'parents.*', 'classrooms.*', 'teachers.*', 'promotion.*'],
            'children' => [
                ['label' => 'Élèves', 'route' => 'students.index', 'permission' => 'voir-eleves'],
                ['label' => 'Inscriptions', 'route' => 'registrations.create', 'permission' => 'creer-inscription'],
                ['label' => 'Réinscription', 'route' => 'registrations.reinscription', 'permission' => 'voir-inscriptions'],
                ['label' => 'Promotion des élèves', 'route' => 'promotion.index', 'permission' => 'promouvoir-eleves'],
                ['label' => 'Classes & Niveaux', 'route' => 'classrooms.index', 'permission' => 'voir-classes'],
            ],
        ],
        [
            'label' => 'Pédagogie',
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            // Pas de 'roles' ici : voir commentaire du groupe 'École'.
            // exclude_roles : un professeur a sa propre entrée "Programmes annuels"/"Cahier
            // de textes" dans "Espace Professeur" (mêmes pages, déjà scopées à ses classes
            // côté contrôleur) — ce groupe générique ferait doublon dans son menu.
            'exclude_roles' => ['professeur'],
            'active_routes' => ['programs.*', 'cahier-textes.*', 'bulletins.*', 'attendances.overview', 'pedagogical-configuration.*'],
            'children' => [
                ['label' => 'Programmes annuels', 'route' => 'programs.index', 'permission' => 'voir-programmes'],
                ['label' => 'Cahier de textes', 'route' => 'cahier-textes.dashboard.index', 'permission' => 'voir-cahier-textes'],
                // MET-10 : ce lien utilisait par erreur 'voir-programmes' (copié depuis
                // l'entrée au-dessus), permission sans rapport avec la route réelle
                // désormais protégée par 'voir-presences' (cf. SEC-02/AttendancePolicy).
                ['label' => 'Présences', 'route' => 'attendances.overview', 'permission' => 'voir-presences'],
                [
                    'label' => 'Configuration pédagogique',
                    'route' => 'pedagogical-configuration.index',
                    'roles' => ['super-admin', 'admin'],
                ],
                // Accès direct plutôt que uniquement via le bouton "Gérer les affectations"
                // de l'onglet "Affectations" de Configuration pédagogique — la liste
                // "Affectations enregistrées" (avec modification/suppression) mérite sa
                // propre entrée, au même niveau que les autres pages de gestion du menu.
                [
                    'label' => 'Affectations pédagogiques',
                    'route' => 'pedagogical-configuration.assignments',
                    'roles' => ['super-admin', 'admin'],
                ],
            ],
        ],
        [
            'label' => 'Finance',
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            // Pas de 'roles' ici : voir commentaire du groupe 'École'. La liste 'permissions'
            // ci-dessous suffit à elle seule (et redevient enfin effective : avec 'roles'
            // présent en même temps, elle n'était jamais évaluée par filterVisible()).
            'permissions' => ['voir-comptabilite', 'voir-paiements', 'valider-paiement-partiel', 'voir-factures', 'voir-recouvrement', 'voir-alertes-impayes', 'voir-types-frais', 'voir-frais-classe', 'voir-rapports-avances', 'voir-tresorerie'],
            'active_routes' => ['accounting.*', 'payments.*', 'invoices.*', 'reminders.*', 'fee-types.*', 'classroom-fees.*'],
            'children' => [
                ['label' => 'Vue financière', 'route' => 'accounting.dashboard', 'permission' => 'voir-comptabilite'],
                // Valider paiements partiels : plus de page dédiée, bouton "Valider" directement
                // sur les lignes concernées de "Paiements".
                ['label' => 'Paiements', 'route' => 'payments.index', 'permission' => 'voir-paiements'],
                ['label' => 'Factures', 'route' => 'invoices.index', 'permission' => 'voir-factures'],
                // voir-alertes-impayes, pas voir-recouvrement : c'est la permission réellement
                // exigée par AccountingController::alerts(). Les deux sont accordées ensemble
                // à tous les rôles actuels, donc sans effet observable aujourd'hui.
                ['label' => 'Impayés & Recouvrement', 'route' => 'accounting.alerts', 'permission' => 'voir-alertes-impayes'],
                ['label' => 'Rappels', 'route' => 'reminders.index', 'roles' => ['super-admin', 'manager-comptable']],
                ['label' => 'Grille tarifaire', 'route' => 'fee-types.index', 'permission' => 'voir-types-frais'],
                ['label' => 'Analyse avancée', 'route' => 'accounting.advanced-reports', 'permission' => 'voir-rapports-avances'],
                ['label' => 'Trésorerie', 'route' => 'accounting.cash-flow', 'permission' => 'voir-tresorerie'],
            ],
        ],
        [
            'label' => 'Administration',
            'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            // Pas de 'roles' ici : voir commentaire du groupe 'École'.
            'active_routes' => ['users.*', 'teachers.*', 'parents.*', 'login-logs.*', 'audit-logs.*', 'announcements.*', 'school-years.*'],
            'children' => [
                // Professeurs et Parents & Tuteurs : intégrés à "Utilisateurs" (filtre par
                // rôle + action "Voir le dossier" par ligne) au lieu de pages de menu
                // séparées — ce sont tous des utilisateurs. Les pages teachers.index/
                // parents.index restent fonctionnelles (tableaux spécialisés : heures pour
                // les professeurs, nombre d'enfants pour les parents) mais ne sont plus
                // dans la navigation principale.
                ['label' => 'Utilisateurs', 'route' => 'users.index', 'permission' => 'voir-utilisateurs'],
                ['label' => 'Attribution des rôles', 'route' => 'users.roles.index', 'permission' => 'modifier-utilisateur'],
                // Nouvelle notification / Historique fusionnées : le formulaire de création
                // est désormais intégré (repliable) en haut de announcements.index — une
                // seule page au lieu de deux entrées de menu pour la même fonctionnalité.
                ['label' => 'Notifications', 'route' => 'announcements.index', 'permissions' => ['voir-historique-notifications', 'creer-notification']],
                // "Logs de connexion" (tentatives d'authentification) remplacée dans le menu
                // par "Audit" (actions métier : créations/modifications/suppressions), plus
                // utile au quotidien — la page login-logs.index reste fonctionnelle (route
                // conservée) mais n'a plus d'entrée de menu dédiée.
                ['label' => 'Audit', 'route' => 'audit-logs.index', 'permission' => 'voir-journal-audit'],
                ['label' => 'Années scolaires', 'route' => 'school-years.index', 'permission' => 'voir-annees-scolaires'],
            ],
        ],

        // Role specific
        [
            'label' => 'Accueil',
            'route' => 'student.dashboard',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'roles' => ['eleve'],
            'active_routes' => ['student.dashboard'],
        ],
        [
            'label' => 'Notifications',
            'route' => 'notifications.index',
            'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            'permission' => 'voir-notifications',
            'roles' => ['eleve'],
            'active_routes' => ['notifications.*'],
        ],
        [
            'label' => 'Espace Comptabilité',
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'roles' => ['comptable'],
            'heading' => true,
            'active_routes' => ['accounting.*', 'payments.*', 'invoices.*', 'fee-types.*', 'classroom-fees.*'],
            'children' => [
                ['label' => 'Dashboard', 'route' => 'accounting.dashboard', 'permission' => 'voir-comptabilite'],
                ['label' => 'Paiements', 'route' => 'payments.index', 'permission' => 'voir-paiements'],
                ['label' => 'Factures', 'route' => 'invoices.index', 'permission' => 'voir-factures'],
                ['label' => 'Types de Frais', 'route' => 'fee-types.index', 'permission' => 'voir-types-frais'],
                ['label' => 'Frais par Classe', 'route' => 'classroom-fees.index', 'permission' => 'voir-frais-classe'],
                ['label' => 'Analyse avancée', 'route' => 'accounting.advanced-reports', 'permission' => 'voir-rapports-avances'],
                ['label' => 'Alertes Impayés', 'route' => 'accounting.alerts', 'permission' => 'voir-alertes-impayes'],
                ['label' => 'Trésorerie', 'route' => 'accounting.cash-flow', 'permission' => 'voir-tresorerie'],
            ],
        ],
        [
            'label' => 'Espace Comptabilité',
            'route' => 'accounting.dashboard',
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'roles' => ['manager-comptable'],
            'permission' => 'voir-comptabilite',
            'active_routes' => ['accounting.*', 'payments.*', 'invoices.*', 'reminders.*', 'fee-types.*', 'classroom-fees.*'],
            'children' => [
                ['label' => 'Dashboard', 'route' => 'accounting.dashboard'],
                ['label' => 'Paiements', 'route' => 'payments.index'],
                ['label' => 'Factures', 'route' => 'invoices.index'],
                ['label' => 'Rappels', 'route' => 'reminders.index', 'roles' => ['manager-comptable']],
                ['label' => 'Types de Frais', 'route' => 'fee-types.index'],
                ['label' => 'Frais par Classe', 'route' => 'classroom-fees.index'],
                ['label' => 'Analyse avancée', 'route' => 'accounting.advanced-reports'],
                ['label' => 'Alertes Impayés', 'route' => 'accounting.alerts'],
                ['label' => 'Trésorerie', 'route' => 'accounting.cash-flow'],
            ],
        ],
        [
            // Tableau de bord du professeur remonté en entrée de premier niveau (même
            // traitement que "Tableau de bord" + "Vue d'ensemble" pour l'admin) plutôt que
            // simple 1er lien noyé dans "Espace Professeur" ci-dessous.
            'label' => 'Tableau de bord',
            'route' => 'professeur.dashboard',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'roles' => ['professeur'],
            'active_routes' => ['professeur.dashboard'],
        ],
        [
            // Le rôle professeur a la permission 'voir-notifications' (RoleAndPermissionSeeder)
            // mais aucune entrée de menu ne pointait vers notifications.index pour lui — les
            // deux entrées existantes du même nom sont chacune restreintes à roles=>['eleve']
            // et roles=>['parent'].
            'label' => 'Notifications',
            'route' => 'notifications.index',
            'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            'permission' => 'voir-notifications',
            'roles' => ['professeur'],
            'active_routes' => ['notifications.*'],
        ],
        [
            // heading => true : section toujours dépliée, pas d'accordéon à ouvrir — un
            // professeur n'a qu'un seul espace de travail, autant que tout soit visible
            // d'un coup (même mécanisme déjà utilisé pour "Espace Comptabilité" du rôle
            // comptable). Programmes annuels et Cahier de textes intégrés ici (au lieu du
            // groupe générique "Pédagogie", exclu pour ce rôle ci-dessus) : mêmes pages,
            // déjà scopées aux classes du professeur connecté côté contrôleur.
            'label' => 'Espace Professeur',
            'icon' => 'M12 14l9-5-9-5-9 5 9 5z',
            'roles' => ['professeur'],
            'heading' => true,
            'active_routes' => ['professeur.*', 'programs.*', 'cahier-textes.*'],
            'children' => [
                ['label' => 'Mes Classes', 'route' => 'professeur.classes.index'],
                // "Saisie Notes par Matricule" fusionnée : un professeur ne saisit jamais que
                // les notes de sa propre matière dans sa propre classe, que l'entrée se fasse
                // par classe ou par élève — plus besoin de deux entrées de menu pour la même
                // portée de données. La recherche par matricule reste accessible directement
                // depuis la page "Saisie Notes" (voir teachers/grades/index.blade.php).
                ['label' => 'Saisie Notes', 'route' => 'professeur.notes.index', 'active_routes' => ['professeur.notes.eleve']],
                ['label' => 'Pointage des cours & présences', 'route' => 'professeur.teaching-sessions.index', 'active_routes' => ['professeur.attendances.index']],
                ['label' => 'Historique présences', 'route' => 'professeur.attendances.history'],
                ['label' => 'Programmes annuels', 'route' => 'programs.index', 'permission' => 'voir-programmes'],
                ['label' => 'Cahier de textes', 'route' => 'cahier-textes.dashboard.index', 'permission' => 'voir-cahier-textes'],
            ],
        ],
        // "Vie Scolaire" et "Suivi Pédagogique" (ci-dessous) : aplaties en liens de premier
        // niveau au lieu de groupes déroulants — un élève n'a qu'une poignée de pages au
        // total, un accordéon à ouvrir pour 2 liens n'apporte rien.
        [
            'label' => 'Emploi du temps',
            'route' => 'student.timetable',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'roles' => ['eleve'],
            'active_routes' => ['student.timetable'],
        ],
        [
            'label' => 'Cahier de texte',
            'route' => 'cahier-textes.select',
            'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'roles' => ['eleve'],
            'permission' => 'voir-cahier-textes',
            'active_routes' => ['cahier-textes.*'],
        ],
        // Parent Portal
        [
            'label' => 'Tableau de bord',
            'route' => 'parents.dashboard',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'roles' => ['parent'],
            'permission' => 'voir-dashboard',
            'active_routes' => ['parents.dashboard'],
        ],
        [
            'label' => 'Mes enfants',
            'route' => 'parents.children.index',
            'icon' => 'M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5z M15 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H15z M5 13a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5z M15 13a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H15z',
            'roles' => ['parent'],
            'permission' => 'voir-ses-enfants',
            'active_routes' => ['parents.children.*'],
        ],
        [
            'label' => 'Messagerie',
            'route' => 'parents.messaging',
            'icon' => 'M8 10h.01M12 10h.01M16 10h.01M21 12c0 3.866-3.134 7-7 7H7l-4 4V5c0-1.105.895-2 2-2h12c3.866 0 7 3.134 7 7z',
            'roles' => ['parent'],
            'permission' => 'voir-messagerie',
            'active_routes' => ['parents.messaging'],
        ],
        [
            'label' => 'Calendrier scolaire',
            'route' => 'parents.calendar',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'roles' => ['parent'],
            'permission' => 'voir-calendrier-scolaire',
            'active_routes' => ['parents.calendar'],
        ],
        [
            'label' => 'Notifications',
            'route' => 'notifications.index',
            'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            'roles' => ['parent'],
            'permission' => 'voir-notifications',
            'active_routes' => ['notifications.*'],
        ],
        [
            'label' => 'Mes Notes',
            'route' => 'student.notes',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'roles' => ['eleve'],
            'active_routes' => ['student.notes'],
        ],
        [
            'label' => 'Bulletins',
            'route' => 'student.bulletins',
            'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'roles' => ['eleve'],
            'active_routes' => ['student.bulletins'],
        ],
    ],
];
