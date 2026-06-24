<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            if (! \Spatie\Permission\Models\Role::whereIn('name', ['comptable', 'manager-comptable'])->exists()) {
                $this->seed(RoleAndPermissionSeeder::class);
            }
        }
    }
}
