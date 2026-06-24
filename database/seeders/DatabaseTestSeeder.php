<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Désactivation des contraintes pour le nettoyage
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('payments')->truncate();
        DB::table('notes')->truncate();
        DB::table('registrations')->truncate();
        DB::table('classroom_matiere')->truncate();
        DB::table('matieres')->truncate();
        DB::table('classrooms')->truncate();
        DB::table('school_years')->truncate();
        User::truncate();

        // Réactivation des contraintes
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Création de l'Année Scolaire avec les colonnes réelles
        $schoolYearId = DB::table('school_years')->insertGetId([
            'year_string' => '2025-2026',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Création de l'Enseignant
        $prof = User::create([
            'matricule' => 'T-2026-001',
            'name' => 'Moustapha Diop',
            'email' => 'moustapha@edumanager.sn',
            'password' => Hash::make('password123'),
            'role' => 'teacher', 
        ]);

        // 4. Création de la Classe liée à l'année scolaire
        $classe = Classroom::create([
            'name' => 'CM1-A',
            'cycle' => 'Primaire',
            'school_year_id' => $schoolYearId,
        ]);

        // 5. Création de l'Élève
        $eleve = User::create([
            'matricule' => 'E-2026-042',
            'name' => 'Amadou Diallo',
            'email' => 'amadou@edumanager.sn',
            'password' => Hash::make('password123'),
            'role' => 'student', 
        ]);

        // 6. Création de la Matière
        $matiere = Matiere::create([
            'nom' => 'Mathématiques',
        ]);

        // 7. Liaison Matière / Classe
        DB::table('classroom_matiere')->insert([
            'classroom_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'coefficient' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 8. Inscription de l'élève
        DB::table('registrations')->insert([
            'user_id' => $eleve->id,
            'classroom_id' => $classe->id,
            'registration_fee_paid' => 25000.00,
            'monthly_fee' => 15000.00,
            'status' => 'active',
            'registration_date' => now()->toDateString(), // Ajout du champ obligatoire
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 9. Ajout de la Note
        Note::create([
            'user_id' => $eleve->id,
            'classroom_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'valeur' => 14.50,
            'type_evaluation' => 'devoir',
            'periode' => 'Trimestre 1',
            'appreciation' => 'Très bon premier devoir',
        ]);
    }
}