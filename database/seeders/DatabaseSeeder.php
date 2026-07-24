<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(TeacherSeeder::class);

        $activeYear = SchoolYear::updateOrCreate(
            ['year_string' => '2025-2026'],
            ['is_active' => true]
        );

        $superAdmin = $this->user('20260325', 'Moustapha Diop', 'moustaff25@gmail.com', 'super-admin');
        $this->user('ADM-260001', 'Admin École', 'admin@edumanager.sn', 'admin');
        $manager = $this->user('MCO-260001', 'Manager Comptable', 'manager.comptable@edumanager.sn', 'manager-comptable');
        $this->user('CPT-260001', 'Comptable', 'comptable@edumanager.sn', 'comptable');
        $teacher = $this->user('PROF001', 'Moussa Sall', 'moussa@ecole.sn', 'professeur');
        Teacher::firstOrCreate(
            ['user_id' => $teacher->id],
            [
                'matricule' => Teacher::generateMatricule(),
                'date_naissance' => '1990-01-01',
                'lieu_naissance' => 'Dakar',
                'sexe' => 'masculin',
                'nationalite' => 'Sénégalaise',
                'diplomes' => 'Non renseigné',
                'etablissements_formation' => 'Non renseigné',
                'statut' => 'contractuel',
                'date_recrutement' => now()->toDateString(),
                'specialites' => [],
                'filiation' => 'Non renseignée',
                'contact_urgence_nom' => 'Non renseigné',
                'contact_urgence_tel' => 'Non renseigné',
                'nombre_heures_semaine' => 0,
            ]
        );
        $studentOne = $this->user('ELE-260001', 'Amadou Diallo', 'amadou@edumanager.sn', 'eleve');
        $studentTwo = $this->user('ELE-260002', 'Aïssatou Ndiaye', 'aissatou@edumanager.sn', 'eleve');

        $cm1 = Classroom::updateOrCreate(
            ['name' => 'CM1 A', 'school_year_id' => $activeYear->id],
            ['cycle' => 'primaire', 'teacher_id' => $teacher->id]
        );

        $sixth = Classroom::updateOrCreate(
            ['name' => '6ème A', 'school_year_id' => $activeYear->id],
            ['cycle' => 'college', 'teacher_id' => null]
        );

        $registrationOne = $this->registration($studentOne, $cm1, $activeYear, 'EDU-26-000001', 'active');
        $registrationTwo = $this->registration($studentTwo, $sixth, $activeYear, 'EDU-26-000002', 'pending');

        Payment::firstOrCreate(
            ['registration_id' => $registrationOne->id, 'month' => 'Octobre'],
            [
                'amount' => 15000,
                'status' => 'complet',
                'remaining_balance' => 0,
                'validated_by' => $manager->id,
            ]
        );

        Payment::firstOrCreate(
            ['registration_id' => $registrationTwo->id, 'month' => 'Octobre'],
            [
                'amount' => 10000,
                'status' => 'partiel',
                'remaining_balance' => 5000,
                'validated_by' => $manager->id,
            ]
        );

        $superAdmin->assignRole('super-admin');
    }

    private function user(string $matricule, string $name, string $email, string $role): User
    {
        $user = User::updateOrCreate(
            ['matricule' => $matricule],
            [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => $role,
                'is_active' => true,
                'password_must_change' => false,
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }

    private function registration(User $student, Classroom $classroom, SchoolYear $schoolYear, string $matricule, string $status): Registration
    {
        return Registration::updateOrCreate(
            ['user_id' => $student->id, 'school_year_id' => $schoolYear->id],
            [
                'classroom_id' => $classroom->id,
                'registration_fee_paid' => 25000,
                'monthly_fee' => 15000,
                'registration_date' => now()->toDateString(),
                'academic_year' => $schoolYear->year_string,
                'matricule' => $matricule,
                'status' => $status,
            ]
        );
    }
}
