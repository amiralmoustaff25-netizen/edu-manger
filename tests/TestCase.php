<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Seeder les rôles/permissions si la table est vide
        if (! Role::where('name', 'super-admin')->exists()) {
            $this->seed(RoleAndPermissionSeeder::class);
        }
    }
}
