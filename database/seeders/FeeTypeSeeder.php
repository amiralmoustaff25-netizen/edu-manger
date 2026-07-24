<?php

namespace Database\Seeders;

use App\Models\FeeType;
use Illuminate\Database\Seeder;

class FeeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Frais d\'inscription',
                'code' => 'inscription',
                'description' => 'Frais d\'inscription unique',
                'is_recurring' => false,
                'is_optional' => false,
            ],
            [
                'name' => 'Scolarité mensuelle',
                'code' => 'mensualite',
                'description' => 'Frais de scolarité mensuelle',
                'is_recurring' => true,
                'is_optional' => false,
            ],
            [
                'name' => 'Cantine',
                'code' => 'cantine',
                'description' => 'Frais de cantine mensuel',
                'is_recurring' => true,
                'is_optional' => true,
            ],
            [
                'name' => 'Transport scolaire',
                'code' => 'transport',
                'description' => 'Frais de transport mensuel',
                'is_recurring' => true,
                'is_optional' => true,
            ],
            [
                'name' => 'Internat',
                'code' => 'internat',
                'description' => 'Frais d\'internat mensuel',
                'is_recurring' => true,
                'is_optional' => true,
            ],
            [
                'name' => 'Frais divers',
                'code' => 'autre',
                'description' => 'Autres frais ponctuels',
                'is_recurring' => false,
                'is_optional' => true,
            ],
        ];

        foreach ($types as $type) {
            FeeType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
