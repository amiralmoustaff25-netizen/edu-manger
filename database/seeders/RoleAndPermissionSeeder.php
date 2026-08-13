<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Le guard utilisé pour les permissions et rôles.
     */
    private const GUARD = 'web';

    public function run(): void
    {
        // Réinitialiser le cache des permissions pour éviter les conflits
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // =================================================================
        // 1. DÉFINITION DE TOUTES LES PERMISSIONS (par module métier)
        // =================================================================

        $permissions = [
            // --- Dashboard & Profil ---
            'voir-dashboard',
            'voir-profil',
            'modifier-profil',
            'supprimer-compte',

            // --- Utilisateurs (users) ---
            'voir-utilisateurs',
            'creer-utilisateur',
            'modifier-utilisateur',
            'supprimer-utilisateur',
            'activer-desactiver-utilisateur',
            'reinitialiser-mot-de-passe-utilisateur',

            // --- Élèves (students) ---
            'voir-eleves',
            'voir-detail-eleve',
            'modifier-eleve',
            'supprimer-eleve',
            'transferer-eleve',
            'modifier-statut-eleve',
            'upload-photo-eleve',
            'gerer-documents-eleve',

            // --- Inscriptions (registrations) ---
            'voir-inscriptions',
            'creer-inscription',

            // --- Parents ---
            'voir-parents',
            'creer-parent',
            'modifier-parent',
            'voir-detail-parent',
            'archiver-parent',
            'restaurer-parent',
            'supprimer-parent',
            'associer-eleve-parent',
            'dissocier-eleve-parent',
            'reinitialiser-mot-de-passe-parent',

            // --- Classes (classrooms) ---
            'voir-classes',
            'creer-classe',
            'modifier-classe',
            'supprimer-classe',
            'affecter-professeur',
            'gerer-enseignants-classe',

            // --- Professeurs ---
            'voir-professeurs',
            'creer-professeur',
            'modifier-professeur',
            'supprimer-professeur',
            'voir-rib-professeur',

            // --- Années scolaires ---
            'voir-annees-scolaires',
            'creer-annee-scolaire',
            'supprimer-annee-scolaire',
            'activer-annee-scolaire',

            // --- Paiements ---
            'voir-paiements',
            'enregistrer-paiement',
            'valider-paiement-partiel',
            'modifier-paiement',
            'supprimer-paiement',
            'annuler-paiement',
            'voir-comptabilite',
            'voir-finances',
            'voir-recouvrement',

            // --- Factures ---
            'voir-factures',
            'creer-facture',
            'modifier-facture',
            'supprimer-facture',

            // --- Types de frais ---
            'voir-types-frais',
            'creer-type-frais',
            'modifier-type-frais',
            'supprimer-type-frais',

            // --- Frais par classe ---
            'voir-frais-classe',
            'creer-frais-classe',
            'modifier-frais-classe',
            'supprimer-frais-classe',
            'gerer-derogations-tarifaires',

            // --- Rapports financiers ---
            'voir-rapports-financiers',
            'voir-rapports-avances',
            'exporter-rapports-excel',
            'voir-alertes-impayes',
            'voir-tresorerie',

            // --- Pédagogie (professeur) ---
            'voir-sa-classe',
            'saisir-notes',
            'valider-notes',
            'rouvrir-notes-validees',
            'marquer-absences',
            'generer-bulletins',

            // --- Parent (espace famille) ---
            'voir-ses-enfants',
            'voir-ses-paiements-enfants',
            'voir-ses-notes-enfants',
            'voir-ses-bulletins-enfants',
            'voir-ses-discipline-enfants',
            'voir-emploi-du-temps',
            'voir-messagerie',
            'voir-calendrier-scolaire',

            // --- Logs & Audit ---
            'voir-logs-connexion',
            'voir-detail-log-connexion',

            // --- Notifications & Communications ---
            'voir-notifications',
            'creer-notification',
            'publier-notification',
            'programmer-notification',
            'modifier-notification',
            'archiver-notification',
            'voir-historique-notifications',
            'voir-statistiques-lecture',

            // --- Cahier de textes & Programmes ---
            'voir-programmes',
            'creer-programme',
            'modifier-programme',
            'soumettre-programme',
            'supprimer-programme',
            'valider-programme-surveillant',
            'valider-programme-directeur',
            'rejeter-programme',
            'voir-cahier-textes',
            'saisir-cahier-textes',
            'modifier-cahier-textes',
            'voir-tableau-bord-cahier-textes',
            'voir-historique-cahier-textes',

            // --- Configuration pédagogique (SEC-02 : ces routes n'avaient aucune
            // permission dédiée et n'étaient protégées que par le rôle au niveau route) ---
            'gerer-configuration-pedagogique',

            // --- Panneau d'administration (SEC-02 : voir-dashboard est partagé par
            // tous les rôles pour le tableau de bord général ; le hub /admin, lui,
            // doit rester réservé à l'administration) ---
            'acceder-panneau-administration',

            // --- Présences (SEC-02 : idem pour la vue d'ensemble /attendances) ---
            'voir-presences',

            // --- Super-Admin ---
            'tout-faire',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => self::GUARD,
            ]);
        }

        // =================================================================
        // 2. CRÉATION DES RÔLES
        // =================================================================

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => self::GUARD,
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => self::GUARD,
        ]);

        $managerComptable = Role::firstOrCreate([
            'name' => 'manager-comptable',
            'guard_name' => self::GUARD,
        ]);

        $comptable = Role::firstOrCreate([
            'name' => 'comptable',
            'guard_name' => self::GUARD,
        ]);

        $surveillant = Role::firstOrCreate([
            'name' => 'surveillant',
            'guard_name' => self::GUARD,
        ]);

        $professeur = Role::firstOrCreate([
            'name' => 'professeur',
            'guard_name' => self::GUARD,
        ]);

        $parent = Role::firstOrCreate([
            'name' => 'parent',
            'guard_name' => self::GUARD,
        ]);

        $eleve = Role::firstOrCreate([
            'name' => 'eleve',
            'guard_name' => self::GUARD,
        ]);

        // =================================================================
        // 3. ATTRIBUTION DES PERMISSIONS PAR RÔLE
        // =================================================================

        // --- Super-Admin : tout ---
        $superAdmin->syncPermissions(Permission::all());

        // --- Admin : tout sauf super-admin, validation/suppression de paiements, finances et rapports ---
        $excludedForAdmin = [
            'tout-faire',
            'valider-paiement-partiel',
            'supprimer-paiement',
            'annuler-paiement',
            // Réouverture d'une note validée : action privilégiée réservée au super-admin.
            'rouvrir-notes-validees',
            'voir-comptabilite',
            'voir-finances',
            'voir-recouvrement',
            'voir-factures',
            'creer-facture',
            'modifier-facture',
            'supprimer-facture',
            'voir-types-frais',
            'creer-type-frais',
            'modifier-type-frais',
            'supprimer-type-frais',
            'voir-frais-classe',
            'creer-frais-classe',
            'modifier-frais-classe',
            'supprimer-frais-classe',
            'gerer-derogations-tarifaires',
            'voir-rapports-financiers',
            'voir-rapports-avances',
            'exporter-rapports-excel',
            'voir-alertes-impayes',
            'voir-tresorerie',
        ];
        $admin->syncPermissions(Permission::whereNotIn('name', $excludedForAdmin)->get());

        // --- Manager-Comptable : finances + paiements ---
        $managerComptable->syncPermissions([
            'voir-dashboard',
            'voir-profil',
            'modifier-profil',
            'voir-notifications',
            'voir-paiements',
            'enregistrer-paiement',
            'valider-paiement-partiel',
            'modifier-paiement',
            'supprimer-paiement',
            'annuler-paiement',
            'voir-comptabilite',
            'voir-finances',
            'voir-recouvrement',
            'voir-factures',
            'creer-facture',
            'modifier-facture',
            'supprimer-facture',
            'voir-types-frais',
            'creer-type-frais',
            'modifier-type-frais',
            'supprimer-type-frais',
            'voir-frais-classe',
            'creer-frais-classe',
            'modifier-frais-classe',
            'supprimer-frais-classe',
            'gerer-derogations-tarifaires',
            'voir-rapports-financiers',
            'voir-rapports-avances',
            'exporter-rapports-excel',
            'voir-alertes-impayes',
            'voir-tresorerie',
            // 'voir-eleves' (liste complète /students) volontairement absente : la
            // route est restreinte à role:super-admin|admin, cette permission serait
            // structurellement inutilisable (même famille que H9-Utilisateurs).
            // 'voir-detail-eleve' reste accordée : elle protège /students/{id}, une
            // route séparée non restreinte par rôle, réellement utile pour consulter
            // un dossier élève dans un contexte financier.
            'voir-detail-eleve',
            'voir-parents',
            'voir-detail-parent',
        ]);

        // --- Comptable : paiements et validation partielle ---
        $comptable->syncPermissions([
            'voir-dashboard',
            'voir-profil',
            'modifier-profil',
            'voir-notifications',
            'voir-paiements',
            'enregistrer-paiement',
            'valider-paiement-partiel',
            'voir-comptabilite',
            'voir-finances',
            'voir-recouvrement',
            'voir-factures',
            'creer-facture',
            'voir-types-frais',
            'voir-frais-classe',
            'voir-rapports-financiers',
            'voir-rapports-avances',
            'exporter-rapports-excel',
            'voir-alertes-impayes',
            'voir-tresorerie',
            'voir-detail-eleve',
            'voir-parents',
            'voir-detail-parent',
        ]);

        // --- Surveillant : validation de programmes ---
        $surveillant->syncPermissions([
            'voir-dashboard',
            'voir-profil',
            'modifier-profil',
            'voir-notifications',
            'voir-programmes',
            'valider-programme-surveillant',
            'rejeter-programme',
            'voir-cahier-textes',
            'voir-tableau-bord-cahier-textes',
        ]);

        // --- Professeur : pédagogie ---
        $professeur->syncPermissions([
            'voir-dashboard',
            'voir-profil',
            'modifier-profil',
            'voir-notifications',
            'voir-sa-classe',
            'saisir-notes',
            'marquer-absences',
            'generer-bulletins',
            'voir-detail-eleve',
            'voir-programmes',
            'creer-programme',
            'modifier-programme',
            'soumettre-programme',
            'voir-cahier-textes',
            'saisir-cahier-textes',
            'modifier-cahier-textes',
            'voir-tableau-bord-cahier-textes',
        ]);

        // --- Parent : espace famille ---
        $parent->syncPermissions([
            'voir-dashboard',
            'voir-profil',
            'modifier-profil',
            'voir-notifications',
            'voir-ses-enfants',
            'voir-ses-paiements-enfants',
            'voir-ses-notes-enfants',
            'voir-ses-bulletins-enfants',
            'voir-ses-discipline-enfants',
            'voir-emploi-du-temps',
            'voir-messagerie',
            'voir-calendrier-scolaire',
        ]);

        // --- Élève : espace personnel ---
        $eleve->syncPermissions([
            'voir-dashboard',
            'voir-profil',
            'modifier-profil',
            'voir-notifications',
        ]);

        // =================================================================
        // 4. SYNCHRONISATION SPATIE ↔ COLONNE users.role (migration temporaire)
        // =================================================================
        // NOTE : Cette section est temporaire. Une fois la migration vers Spatie
        // terminée, supprimer la colonne users.role et cette section.

        if (Schema::hasColumn('users', 'role')) {
            $roleMapping = [
                'super-admin' => 'super-admin',
                'admin' => 'admin',
                'manager-comptable' => 'manager-comptable',
                'comptable' => 'comptable',
                'surveillant' => 'surveillant',
                'professeur' => 'professeur',
                'parent' => 'parent',
                'eleve' => 'eleve',
            ];

            foreach ($roleMapping as $columnRole => $spatieRole) {
                User::where('role', $columnRole)
                    ->whereDoesntHave('roles', function ($query) use ($spatieRole) {
                        $query->where('name', $spatieRole);
                    })
                    ->get()
                    ->each(function ($user) use ($spatieRole) {
                        $user->assignRole($spatieRole);
                    });
            }
        }

        // =================================================================
        // 5. CRÉATION DU SUPER-ADMIN PAR DÉFAUT (si aucun n'existe)
        // =================================================================
        // SEC-01 : un compte à identifiants prévisibles (email fixe, mot de
        // passe "password" en dur, visibles dans le code source public) ne
        // doit JAMAIS être créé automatiquement en production — un
        // attaquant connaissant ce pattern pourrait prendre le contrôle de
        // l'application avant l'équipe légitime. Cette création automatique
        // reste utile en local/testing (bootstrap rapide, fixtures de
        // test) ; en production, utiliser la commande dédiée
        // `php artisan admin:create-super-admin`, qui génère un mot de
        // passe aléatoire affiché une seule fois.
        if (app()->environment('local') && ! User::role('super-admin')->exists()) {
            $superAdminUser = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@edu-manager.local',
                'password' => bcrypt('password'),
                'matricule' => 'ADM-'.date('Y').'-0001',
                'role' => 'super-admin',
                'is_active' => true,
                'password_must_change' => true,
                'created_by' => null,
            ]);

            $superAdminUser->assignRole('super-admin');
        } elseif (! app()->environment(['local', 'testing']) && ! User::role('super-admin')->exists() && isset($this->command)) {
            // Ce message n'est pas affiché en 'testing' (pas de sortie console dans les tests).
            $this->command?->warn(
                'Aucun compte super-admin trouvé. En production, créez-en un avec : php artisan admin:create-super-admin'
            );
        }

        // Re-cacher les permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
