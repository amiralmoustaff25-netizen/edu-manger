<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        // SEC-02 : 'voir-dashboard' est accordée à tous les rôles pour le tableau
        // de bord général — ce hub /admin doit rester réservé à l'administration
        // (auparavant garanti uniquement par le middleware de route, retiré depuis).
        $this->authorize('acceder-panneau-administration');

        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $modules = [
            [
                'key' => 'scolarite',
                'title' => 'Scolarité',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'items' => [
                    ['label' => 'Élèves', 'route' => 'students.index', 'permission' => 'voir-eleves'],
                    ['label' => 'Inscriptions', 'route' => 'registrations.create', 'permission' => 'creer-inscription'],
                    ['label' => 'Parents & Tuteurs', 'route' => 'parents.index', 'permission' => 'voir-parents'],
                ],
            ],
            [
                'key' => 'pedagogie',
                'title' => 'Organisation pédagogique',
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'items' => [
                    ['label' => 'Classes & Niveaux', 'route' => 'classrooms.index', 'permission' => 'voir-classes'],
                    ['label' => 'Professeurs', 'route' => 'teachers.index', 'permission' => 'voir-professeurs'],
                    ['label' => 'Programmes annuels', 'route' => 'programs.index', 'permission' => 'voir-programmes'],
                    ['label' => 'Cahier de textes', 'route' => 'cahier-textes.dashboard.index', 'permission' => 'voir-cahier-textes'],
                    ['label' => 'Bulletins', 'route' => 'bulletins.index', 'permission' => null],
                    ['label' => 'Configuration pédagogique', 'route' => 'pedagogical-configuration.index', 'permission' => null],
                    ['label' => 'Présences', 'route' => 'attendances.overview', 'permission' => null],
                ],
            ],
            [
                'key' => 'finance',
                'title' => 'Finance',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'items' => [
                    ['label' => 'Vue financière', 'route' => 'accounting.dashboard', 'permission' => 'voir-comptabilite'],
                    ['label' => 'Paiements', 'route' => 'payments.index', 'permission' => 'voir-paiements'],
                    ['label' => 'Factures', 'route' => 'invoices.index', 'permission' => 'voir-factures'],
                    ['label' => 'Impayés & Recouvrement', 'route' => 'accounting.alerts', 'permission' => 'voir-recouvrement'],
                    ['label' => 'Rappels', 'route' => 'reminders.index', 'permission' => null, 'roles' => ['super-admin', 'manager-comptable']],
                    ['label' => 'Grille tarifaire', 'route' => 'fee-types.index', 'permission' => 'voir-types-frais'],
                ],
            ],
            [
                'key' => 'users',
                'title' => 'Utilisateurs & Accès',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'items' => [
                    ['label' => 'Utilisateurs', 'route' => 'users.index', 'permission' => 'voir-utilisateurs'],
                    ['label' => 'Attribution des rôles et accès', 'route' => 'users.roles.index', 'permission' => 'modifier-utilisateur'],
                    ['label' => 'Logs de connexion', 'route' => 'login-logs.index', 'permission' => 'voir-logs-connexion'],
                ],
            ],
            [
                'key' => 'reports',
                'title' => 'Rapports & Analyse',
                'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'items' => [
                    ['label' => 'Rapports financiers', 'route' => 'accounting.reports', 'permission' => 'voir-rapports-financiers'],
                    ['label' => 'Analyse avancée', 'route' => 'accounting.advanced-reports', 'permission' => 'voir-rapports-avances'],
                    ['label' => 'Trésorerie', 'route' => 'accounting.cash-flow', 'permission' => 'voir-tresorerie'],
                ],
            ],
            [
                'key' => 'admin',
                'title' => 'Paramètres système',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'items' => [
                    ['label' => 'Années scolaires', 'route' => 'school-years.index', 'permission' => 'voir-annees-scolaires'],
                    ['label' => 'Configuration pédagogique', 'route' => 'pedagogical-configuration.index', 'permission' => null],
                ],
            ],
            [
                'key' => 'communications',
                'title' => 'Notifications & Communications',
                'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                'items' => [
                    // Fusionnées : le formulaire de création est intégré (repliable) en
                    // haut de announcements.index, une seule page désormais.
                    ['label' => 'Notifications', 'route' => 'announcements.index', 'permission' => 'voir-historique-notifications'],
                ],
            ],
        ];

        $visibleModules = collect($modules)
            ->map(function ($module) use ($user, $isSuperAdmin) {
                if (! empty($module['super_admin_only']) && ! $isSuperAdmin) {
                    return null;
                }

                $items = collect($module['items'])
                    ->filter(function ($item) use ($user, $isSuperAdmin) {
                        if (! empty($item['super_admin_only']) && ! $isSuperAdmin) {
                            return false;
                        }

                        if (! empty($item['roles']) && ! $user->hasAnyRole($item['roles'])) {
                            return false;
                        }

                        if (empty($item['permission'])) {
                            return true;
                        }

                        return $user->can($item['permission']);
                    })
                    ->values();

                return $items->isEmpty() ? null : array_merge($module, ['items' => $items]);
            })
            ->filter()
            ->values();

        return view('admin.dashboard', ['modules' => $visibleModules, 'isSuperAdmin' => $isSuperAdmin]);
    }
}
