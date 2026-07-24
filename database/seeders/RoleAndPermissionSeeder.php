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
            'transferer-eleve',
            'modifier-statut-eleve',
            'upload-photo-eleve',

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

            // --- Rapports financiers ---
            'voir-rapports-financiers',
            'voir-rapports-avances',
            'exporter-rapports-excel',
            'voir-alertes-impayes',
            'voir-tresorerie',

            // --- Pédagogie (professeur) ---
            'voir-sa-classe',
            'saisir-notes',
            'marquer-absences',

            // --- Parent (espace famille) ---
            'voir-ses-enfants',
            'voir-ses-paiements-enfants',
            'voir-ses-notes-enfants',

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

        // --- Admin : tout sauf super-admin ---
        $admin->syncPermissions(Permission::where('name', '!=', 'tout-faire')->get());

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
            'voir-rapports-financiers',
            'voir-rapports-avances',
            'exporter-rapports-excel',
            'voir-alertes-impayes',
            'voir-tresorerie',
            'voir-eleves',
            'voir-detail-eleve',
            'voir-parents',
            'voir-detail-parent',
        ]);

        // --- Comptable : paiements sans validation partielle ---
        $comptable->syncPermissions([
            'voir-dashboard',
            'voir-profil',
            'modifier-profil',
            'voir-notifications',
            'voir-paiements',
            'enregistrer-paiement',
            'voir-comptabilite',
            'voir-finances',
            'voir-recouvrement',
            'voir-factures',
            'creer-facture',
            'voir-types-frais',
            'creer-type-frais',
            'voir-frais-classe',
            'creer-frais-classe',
            'voir-rapports-financiers',
            'voir-rapports-avances',
            'exporter-rapports-excel',
            'voir-alertes-impayes',
            'voir-tresorerie',
            'voir-eleves',
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
            'voir-eleves',
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

        if (! User::role('super-admin')->exists()) {
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
        }

        // Re-cacher les permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
