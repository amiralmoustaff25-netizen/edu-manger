<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private $registrationId;

    protected function setUp(): void
    {
        parent::setUp();

        // Préparation des données minimales requises pour le test
        $schoolYearId = DB::table('school_years')->insertGetId([
            'year_string' => '2025-2026',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classe = Classroom::create([
            'name' => 'CM1-A',
            'cycle' => 'Primaire',
            'school_year_id' => $schoolYearId,
        ]);

        $eleve = User::create([
            'matricule' => 'E-TEST-001',
            'name' => 'Élève Test',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $this->registrationId = DB::table('registrations')->insertGetId([
            'user_id' => $eleve->id,
            'classroom_id' => $classe->id,
            'registration_fee_paid' => 25000.00,
            'monthly_fee' => 15000.00,
            'status' => 'active',
            'registration_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function un_comptable_simple_peut_valider_un_paiement_partiel()
    {
        $comptable = User::create([
            'matricule' => 'C-COMPTA-01',
            'name' => 'Comptable Simple',
            'password' => bcrypt('password'),
            'role' => 'comptable',
        ]);
        $comptable->assignRole('comptable');

        // Utilisation du guard par défaut en omettant le second paramètre de actingAs
        $response = $this->actingAs($comptable)
            ->postJson(route('payments.store'), [
                'registration_id' => $this->registrationId,
                'amount_paid' => 10000.00,
                'month' => 'Octobre',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'registration_id' => $this->registrationId,
            'amount' => 10000.00,
            'status' => 'partiel',
            'remaining_balance' => 5000.00,
            'validated_by' => $comptable->id,
            'payment_method' => 'espèces',
            'payment_type' => 'mensualité',
        ]);
    }

    #[Test]
    public function un_manager_comptable_peut_valider_un_paiement_partiel()
    {
        $manager = User::create([
            'matricule' => 'M-MANAGER-01',
            'name' => 'Manager Comptable',
            'password' => bcrypt('password'),
            'role' => 'manager_comptable',
        ]);
        $manager->assignRole('manager-comptable');

        $response = $this->actingAs($manager)
            ->postJson(route('payments.store'), [
                'registration_id' => $this->registrationId,
                'amount_paid' => 10000.00,
                'month' => 'Octobre',
                // ✅ Plus besoin de payment_method, payment_type, comment
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'registration_id' => $this->registrationId,
            'amount' => 10000.00,
            'status' => 'partiel',
            'remaining_balance' => 5000.00,
            'validated_by' => $manager->id,
            'payment_method' => 'espèces',      // valeur par défaut
            'payment_type' => 'mensualité',     // valeur par défaut
        ]);
    }
}
