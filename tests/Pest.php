<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)
    ->in('Feature');

uses(TestCase::class)
    ->in('Unit');

// Pas de uses(RefreshDatabase::class) ici : Tests\TestCase l'utilise déjà et personnalise
// migrateDatabases() pour semer roles/permissions une seule fois par run (voir ce fichier).
// L'appliquer une seconde fois ici re-fusionne la méthode du trait directement sur les
// classes générées par Pest, qui prime alors sur l'override hérité de Tests\TestCase (les
// méthodes définies/fusionnées sur une classe l'emportent toujours sur celles héritées d'un
// ancêtre, même quand cet ancêtre les redéfinit) — l'override était donc silencieusement
// ignoré, chaque test reseedait les mêmes permissions et finissait par provoquer un
// deadlock sous MySQL (cf. tests/TestCase.php).

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function actingAs($user, $guard = null)
{
    return test()->actingAs($user, $guard);
}
