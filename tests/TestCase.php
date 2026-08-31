<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    // migrateDatabases() est surchargée ci-dessous ; l'alias donne accès à
    // l'implémentation d'origine du trait, que "parent::migrateDatabases()" ne peut pas
    // atteindre (un trait n'entre pas dans la chaîne d'héritage "parent::", seule la
    // vraie classe parente y figure).
    use RefreshDatabase {
        migrateDatabases as protected refreshDatabaseMigrateDatabases;
    }

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * migrateDatabases(), pas afterRefreshingDatabase() : ce dernier est appelé après
     * CHAQUE test (malgré son nom), donc toujours à l'intérieur de la transaction que
     * RefreshDatabase annule à la fin de ce même test — un seed ici reproduirait à
     * l'identique l'ancien défaut ("if (! Role::where(...)->exists())" dans setUp()) :
     * réinsertion des mêmes clés uniques des centaines de fois, purgées à chaque
     * rollback. Sous MySQL, ce martèlement finissait par provoquer un deadlock
     * artefactuel (jamais reproduit sous SQLite, dépourvu de ce verrouillage).
     * migrateDatabases() n'est en revanche exécuté qu'une seule fois par run, avant
     * que la moindre transaction de test ne démarre (voir RefreshDatabase::
     * refreshTestDatabase()) : le seed y commit normalement, et chaque test en hérite
     * ensuite via l'instantané de sa propre transaction, sans jamais le réinsérer.
     */
    protected function migrateDatabases()
    {
        $this->refreshDatabaseMigrateDatabases();

        $this->seed(RoleAndPermissionSeeder::class);
    }
}
