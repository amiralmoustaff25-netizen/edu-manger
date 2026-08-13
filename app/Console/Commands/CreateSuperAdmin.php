<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * SEC-01 : remplace la création automatique d'un super-admin à identifiants
 * prévisibles (email fixe + mot de passe "password" en dur) en production.
 * Le mot de passe temporaire est généré aléatoirement et affiché une seule
 * fois en sortie de commande — jamais stocké ailleurs qu'en base (haché).
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-super-admin {--email=} {--name=} {--force : Créer même si un super-admin existe déjà}';

    protected $description = 'Créer un compte super-admin avec un mot de passe temporaire aléatoire (usage : bootstrap de production)';

    public function handle(AuditLogService $auditLog): int
    {
        if (! $this->option('force') && User::role('super-admin')->exists()) {
            $this->error('Un compte super-admin existe déjà. Utilisez --force pour en créer un supplémentaire, ou réinitialisez le mot de passe d\'un compte existant.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Nom complet du super-admin');
        $email = $this->option('email') ?: $this->ask('Adresse e-mail du super-admin');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email']]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $temporaryPassword = Str::password(16);

        $superAdmin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($temporaryPassword),
            'matricule' => User::generateMatricule('super-admin'),
            'role' => 'super-admin',
            'is_active' => true,
            'password_must_change' => true,
            'created_by' => null,
        ]);

        $superAdmin->assignRole('super-admin');
        $superAdmin->syncPrimaryRoleColumn();

        $auditLog->log(
            action: 'super_admin_created_via_console',
            modelType: User::class,
            modelId: $superAdmin->id,
            newValues: ['email' => $email, 'created_via' => 'admin:create-super-admin'],
            comment: 'Compte super-admin créé via la commande console (bootstrap).'
        );

        $this->newLine();
        $this->info('Compte super-admin créé avec succès.');
        $this->line("  Email       : {$email}");
        $this->line("  Matricule   : {$superAdmin->matricule}");
        $this->warn("  Mot de passe temporaire : {$temporaryPassword}");
        $this->newLine();
        $this->comment('Ce mot de passe ne sera plus jamais affiché. Notez-le immédiatement et transmettez-le de façon sécurisée. Un changement de mot de passe sera exigé à la première connexion.');

        return self::SUCCESS;
    }
}
