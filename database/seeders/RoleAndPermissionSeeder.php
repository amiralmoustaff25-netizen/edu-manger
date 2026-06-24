<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'creer-classe',
            'inscrire-eleve',
            'gerer-utilisateurs',
            'enregistrer-paiement',
            'valider-paiement-partiel',
            'voir-finances',
            'voir-classes',
            'modifier-classe',
            'supprimer-classe',
            'affecter-professeur',
            'voir-sa-classe',
            'saisir-notes',
            'marquer-absences',
            'voir-ses-enfants',
            'voir-ses-paiements-enfants',
            'voir-ses-notes-enfants',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $managerComptable = Role::firstOrCreate(['name' => 'manager-comptable']);
        $comptable = Role::firstOrCreate(['name' => 'comptable']);
        $professeur = Role::firstOrCreate(['name' => 'professeur']);
        $parent = Role::firstOrCreate(['name' => 'parent']);
        $eleve = Role::firstOrCreate(['name' => 'eleve']);

        $superAdmin->syncPermissions([
            'creer-classe',
            'inscrire-eleve',
            'gerer-utilisateurs',
            'voir-classes',
            'modifier-classe',
            'supprimer-classe',
            'affecter-professeur',
        ]);

        $admin->syncPermissions([
            'creer-classe',
            'inscrire-eleve',
            'gerer-utilisateurs',
            'voir-classes',
            'modifier-classe',
            'supprimer-classe',
            'affecter-professeur',
        ]);

        $managerComptable->syncPermissions([
            'enregistrer-paiement',
            'valider-paiement-partiel',
            'voir-finances',
        ]);

        $comptable->syncPermissions([
            'enregistrer-paiement',
            'voir-finances',
        ]);

        $professeur->syncPermissions([
            'voir-sa-classe',
            'saisir-notes',
            'marquer-absences',
        ]);

        $parent->syncPermissions([
            'voir-ses-enfants',
            'voir-ses-paiements-enfants',
            'voir-ses-notes-enfants',
        ]);

        $eleve->syncPermissions([]);

        User::where('role', 'super-admin')->get()->each->assignRole('super-admin');
        User::where('role', 'admin')->get()->each->assignRole('admin');
        User::where('role', 'manager-comptable')->get()->each->assignRole('manager-comptable');
        User::where('role', 'comptable')->get()->each->assignRole('comptable');
        User::where('role', 'professeur')->get()->each->assignRole('professeur');
        User::where('role', 'parent')->get()->each->assignRole('parent');
        User::where('role', 'eleve')->get()->each->assignRole('eleve');
    }
}
