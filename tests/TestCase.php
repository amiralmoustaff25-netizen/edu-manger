<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Seeder les rôles/permissions si la table est vide
        if (!\Spatie\Permission\Models\Role::where('name', 'super-admin')->exists()) {
            $this->seed(RoleAndPermissionSeeder::class);
        }
    }
}