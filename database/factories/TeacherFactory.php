<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'professeur']),
            'matricule' => 'PROF-'.date('y').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'date_naissance' => now()->subYears(35)->subDays(120)->format('Y-m-d'),
            'lieu_naissance' => 'Dakar',
            'sexe' => 'masculin',
            'nationalite' => 'Sénégalaise',
            'diplomes' => 'Licence en sciences de l’éducation',
            'etablissements_formation' => 'Université Cheikh Anta Diop',
            'statut' => 'fonctionnaire',
            'date_recrutement' => now()->subYears(5)->subMonths(2)->format('Y-m-d'),
            'specialites' => ['Mathématiques', 'Physique'],
            'filiation' => 'Fils de M. Diallo',
            'contact_urgence_nom' => 'M. Diallo',
            'contact_urgence_tel' => '770000000',
            'rib' => null,
            'nombre_heures_semaine' => 16,
            'created_by' => null,
        ];
    }
}
