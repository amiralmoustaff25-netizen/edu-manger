<?php

return [
    'items' => [
        // Admin / Super-Admin common
        [
            'label' => 'Tableau de bord',
            'route' => 'dashboard',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'permission' => 'voir-dashboard',
            'exclude_roles' => ['eleve'],
        ],
        [
            'label' => 'Administration',
            'route' => 'admin.dashboard',
            'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            'roles' => ['super-admin', 'admin'],
        ],
        [
            'label' => 'Scolarité',
            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            'roles' => ['super-admin', 'admin'],
            'active_routes' => ['students.*', 'registrations.*', 'parents.*'],
            'children' => [
                ['label' => 'Élèves', 'route' => 'students.index', 'permission' => 'voir-eleves'],
                ['label' => 'Inscriptions', 'route' => 'registrations.create', 'permission' => 'creer-inscription'],
                ['label' => 'Parents & Tuteurs', 'route' => 'parents.index', 'permission' => 'voir-parents'],
            ],
        ],
        [
            'label' => 'Pédagogie',
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'roles' => ['super-admin', 'admin'],
            'active_routes' => ['classrooms.*', 'teachers.*', 'programs.*', 'cahier-textes.*', 'bulletins.*', 'attendances.overview', 'pedagogical-configuration.*'],
            'children' => [
                ['label' => 'Classes & Niveaux', 'route' => 'classrooms.index', 'permission' => 'voir-classes'],
                ['label' => 'Professeurs', 'route' => 'teachers.index', 'permission' => 'voir-professeurs'],
                ['label' => 'Programmes annuels', 'route' => 'programs.index', 'permission' => 'voir-programmes'],
                ['label' => 'Cahier de textes', 'route' => 'cahier-textes.dashboard.index', 'permission' => 'voir-cahier-textes'],
                ['label' => 'Bulletins', 'route' => 'bulletins.index', 'permission' => 'voir-programmes'],
                ['label' => 'Présences', 'route' => 'attendances.overview', 'permission' => 'voir-programmes'],
                [
                    'label' => 'Configuration pédagogique',
                    'route' => 'pedagogical-configuration.index',
                    'roles' => ['super-admin', 'admin'],
                ],
            ],
        ],
        [
            'label' => 'Finance',
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'permissions' => ['voir-comptabilite', 'voir-paiements', 'voir-factures', 'voir-recouvrement', 'voir-types-frais', 'voir-frais-classe'],
            'active_routes' => ['accounting.*', 'payments.*', 'invoices.*', 'reminders.*', 'fee-types.*', 'classroom-fees.*'],
            'children' => [
                ['label' => 'Vue financière', 'route' => 'accounting.dashboard', 'permission' => 'voir-comptabilite'],
                ['label' => 'Paiements', 'route' => 'payments.index', 'permission' => 'voir-paiements'],
                ['label' => 'Factures', 'route' => 'invoices.index', 'permission' => 'voir-factures'],
                ['label' => 'Impayés & Recouvrement', 'route' => 'accounting.alerts', 'permission' => 'voir-recouvrement'],
                ['label' => 'Rappels', 'route' => 'reminders.index', 'roles' => ['super-admin', 'manager-comptable']],
                ['label' => 'Grille tarifaire', 'route' => 'fee-types.index', 'permission' => 'voir-types-frais'],
            ],
        ],
        [
            'label' => 'Utilisateurs & Accès',
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            'roles' => ['super-admin', 'admin'],
            'active_routes' => ['users.*', 'login-logs.*'],
            'children' => [
                ['label' => 'Utilisateurs', 'route' => 'users.index', 'permission' => 'voir-utilisateurs'],
                ['label' => 'Attribution des rôles', 'route' => 'users.roles.index', 'permission' => 'modifier-utilisateur'],
                ['label' => 'Logs de connexion', 'route' => 'login-logs.index', 'permission' => 'voir-logs-connexion'],
            ],
        ],
        [
            'label' => 'Rapports',
            'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'permissions' => ['voir-rapports-financiers', 'voir-rapports-avances', 'voir-tresorerie', 'voir-recouvrement'],
            'active_routes' => ['accounting.reports', 'accounting.advanced-reports', 'accounting.cash-flow', 'accounting.alerts'],
            'children' => [
                ['label' => 'Rapports financiers', 'route' => 'accounting.reports', 'permission' => 'voir-rapports-financiers'],
                ['label' => 'Analyse avancée', 'route' => 'accounting.advanced-reports', 'permission' => 'voir-rapports-avances'],
                ['label' => 'Trésorerie', 'route' => 'accounting.cash-flow', 'permission' => 'voir-tresorerie'],
                ['label' => 'Alertes impayés', 'route' => 'accounting.alerts', 'permission' => 'voir-recouvrement'],
            ],
        ],
        [
            'label' => 'Communications',
            'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            'roles' => ['super-admin', 'admin'],
            'active_routes' => ['announcements.*'],
            'children' => [
                ['label' => 'Nouvelle notification', 'route' => 'announcements.create', 'permission' => 'creer-notification'],
                ['label' => 'Historique', 'route' => 'announcements.index', 'permission' => 'voir-historique-notifications'],
            ],
        ],
        [
            'label' => 'Paramètres',
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'roles' => ['super-admin', 'admin'],
            'active_routes' => ['school-years.*'],
            'children' => [
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
            'roles' => ['manager-comptable', 'comptable'],
            'active_routes' => ['accounting.*', 'payments.*', 'invoices.*', 'reminders.*', 'fee-types.*', 'classroom-fees.*'],
            'children' => [
                ['label' => 'Dashboard', 'route' => 'accounting.dashboard'],
                ['label' => 'Paiements', 'route' => 'payments.index'],
                ['label' => 'Factures', 'route' => 'invoices.index'],
                ['label' => 'Rappels', 'route' => 'reminders.index', 'roles' => ['manager-comptable']],
                ['label' => 'Types de Frais', 'route' => 'fee-types.index'],
                ['label' => 'Frais par Classe', 'route' => 'classroom-fees.index'],
                ['label' => 'Rapports Financiers', 'route' => 'accounting.reports'],
                ['label' => 'Rapports Avancés', 'route' => 'accounting.advanced-reports'],
                ['label' => 'Alertes Impayés', 'route' => 'accounting.alerts'],
                ['label' => 'Trésorerie', 'route' => 'accounting.cash-flow'],
            ],
        ],
        [
            'label' => 'Espace Professeur',
            'icon' => 'M12 14l9-5-9-5-9 5 9 5z',
            'roles' => ['professeur'],
            'active_routes' => ['professeur.*'],
            'children' => [
                ['label' => 'Tableau de bord', 'route' => 'professeur.dashboard'],
                ['label' => 'Mes Classes', 'route' => 'professeur.classes.index'],
                ['label' => 'Saisie Notes', 'route' => 'professeur.notes.index'],
                ['label' => 'Pointage des cours', 'route' => 'professeur.teaching-sessions.index'],
                ['label' => 'Présences', 'route' => 'professeur.attendances.index'],
                ['label' => 'Historique présences', 'route' => 'professeur.attendances.history'],
            ],
        ],
        [
            'label' => 'Vie Scolaire',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'roles' => ['eleve'],
            'active_routes' => ['student.timetable', 'cahier-textes.*'],
            'children' => [
                ['label' => 'Emploi du temps', 'route' => 'student.timetable'],
                ['label' => 'Cahier de texte', 'route' => 'cahier-textes.select', 'permission' => 'voir-cahier-textes'],
            ],
        ],
        [
            'label' => 'Suivi Pédagogique',
            'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'roles' => ['eleve'],
            'active_routes' => ['student.notes', 'student.bulletins'],
            'children' => [
                ['label' => 'Mes Notes', 'route' => 'student.notes'],
                ['label' => 'Bulletins', 'route' => 'student.bulletins'],
            ],
        ],

        // Global
        [
            'label' => 'Mon Profil',
            'route' => 'profile.show',
            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'active_routes' => ['profile.*'],
        ],
    ],
];
