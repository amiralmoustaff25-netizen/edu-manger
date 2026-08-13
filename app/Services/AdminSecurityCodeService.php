<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Code de sécurité Super Admin : un secret distinct du mot de passe de
 * connexion, exigé en plus de l'authentification normale pour certaines
 * actions critiques (voir VerifySecurityCode). Volontairement porté par
 * chaque compte super-admin individuellement (pas un secret global partagé) :
 * un code compromis ou un départ d'équipe n'affecte que ce compte.
 */
class AdminSecurityCodeService
{
    public function hasCode(User $user): bool
    {
        return ! empty($user->security_code);
    }

    /**
     * Définit ou remplace le code. L'appelant (contrôleur) est responsable de
     * revérifier l'identité (mot de passe actuel) avant d'appeler cette méthode
     * — ce service ne fait que le hachage/la persistance, pas la vérification
     * d'identité préalable.
     */
    public function setCode(User $user, string $code): void
    {
        $user->forceFill([
            'security_code' => Hash::make($code),
            'security_code_updated_at' => now(),
        ])->save();
    }

    public function verifyCode(User $user, ?string $code): bool
    {
        if (! $this->hasCode($user)) {
            return false;
        }

        return $code !== null && Hash::check($code, $user->security_code);
    }

    /**
     * Point d'entrée pour protéger une action critique existante. Tant que
     * l'utilisateur n'a pas défini de code, l'action reste accessible sans
     * blocage (déploiement progressif, cf. migration) : ce n'est qu'une fois
     * le code défini que la vérification devient obligatoire pour CE compte.
     */
    public function ensureVerified(User $user, ?string $providedCode): void
    {
        if (! $this->hasCode($user)) {
            return;
        }

        if (! $this->verifyCode($user, $providedCode)) {
            throw ValidationException::withMessages([
                'security_code' => 'Code de sécurité incorrect. Cette action nécessite votre code de sécurité administrateur.',
            ]);
        }
    }
}
