<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            [
                'name' => 'Aminata Diop',
                'email' => 'aminata.diop@ecole.sn',
                'statut' => 'fonctionnaire',
                'specialites' => ['Mathématiques', 'Physique'],
            ],
            [
                'name' => 'Mamadou Fall',
                'email' => 'mamadou.fall@ecole.sn',
                'statut' => 'contractuel',
                'specialites' => ['Français', 'Histoire'],
            ],
            [
                'name' => 'Khady Sarr',
                'email' => 'khady.sarr@ecole.sn',
                'statut' => 'vacataire',
                'specialites' => ['Anglais', 'Espagnol'],
            ],
            [
                'name' => 'Cheikh Ndour',
                'email' => 'cheikh.ndour@ecole.sn',
                'statut' => 'fonctionnaire',
                'specialites' => ['SVT', 'Technologie'],
            ],
            [
                'name' => 'Fatoumatou Sy',
                'email' => 'fatoumatou.sy@ecole.sn',
                'statut' => 'contractuel',
                'specialites' => ['Informatique', 'Mathématiques'],
            ],
        ];

        foreach ($teachers as $index => $data) {
            $user = User::updateOrCreate([
                'email' => $data['email'],
            ], [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'matricule' => User::generateMatricule('professeur'),
                'role' => 'professeur',
                'is_active' => true,
                'password_must_change' => true,
            ]);

            $user->syncRoles(['professeur']);

            Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'matricule' => Teacher::generateMatricule(),
                    'date_naissance' => now()->subYears(30 + $index)->format('Y-m-d'),
                    'lieu_naissance' => 'Dakar',
                    'sexe' => $index % 2 === 0 ? 'feminin' : 'masculin',
                    'nationalite' => 'Sénégalaise',
                    'diplomes' => 'Licence',
                    'etablissements_formation' => 'Université Cheikh Anta Diop',
                    'statut' => $data['statut'],
                    'date_recrutement' => now()->subYears(5 - $index)->format('Y-m-d'),
                    'specialites' => $data['specialites'],
                    'filiation' => 'Fils de M. Sy',
                    'contact_urgence_nom' => 'Parent d’urgence',
                    'contact_urgence_tel' => '77000000'.($index + 1),
                    'nombre_heures_semaine' => 14 + ($index * 2),
                    'created_by' => null,
                ]
            );
        }
    }
}
