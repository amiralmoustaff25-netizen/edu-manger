<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Auto-inscription/désactivation par le Super Admin de sa propre double
 * authentification (TOTP). Distinct de TwoFactorChallengeController, qui gère
 * la vérification du code à la connexion une fois l'activation confirmée.
 */
class TwoFactorAuthenticationController extends Controller
{
    public function show(Request $request, TwoFactorAuthenticationService $totp): View
    {
        Gate::authorize('gerer-double-authentification');

        $user = $request->user();

        if ($user->two_factor_confirmed_at) {
            return view('auth.two-factor.show', ['enabled' => true, 'confirmedAt' => $user->two_factor_confirmed_at]);
        }

        // Un secret "en attente" (généré mais pas encore confirmé) est conservé en
        // session, pas en base : tant que l'utilisateur n'a pas prouvé qu'il l'a
        // correctement enregistré dans son application d'authentification (en
        // renvoyant un code valide), l'activation ne doit pas être considérée
        // effective — voir confirm().
        $pendingSecret = $request->session()->get('two_factor_pending_secret');

        if (! $pendingSecret) {
            $pendingSecret = $totp->generateSecretKey();
            $request->session()->put('two_factor_pending_secret', $pendingSecret);
        }

        return view('auth.two-factor.show', [
            'enabled' => false,
            'secret' => $pendingSecret,
            'provisioningUri' => $totp->getProvisioningUri($user, $pendingSecret),
        ]);
    }

    public function confirm(Request $request, TwoFactorAuthenticationService $totp, AuditLogService $auditLog): RedirectResponse
    {
        Gate::authorize('gerer-double-authentification');

        $validated = $request->validate(['code' => ['required', 'string']]);

        $secret = $request->session()->get('two_factor_pending_secret');

        if (! $secret) {
            return redirect()->route('two-factor.show')->withErrors(['code' => 'Aucune activation en cours. Recommencez.']);
        }

        if (! $totp->verify($secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Code invalide. Vérifiez l\'heure de votre appareil et réessayez.']);
        }

        $user = $request->user();
        $recoveryCodes = $totp->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor_pending_secret');
        // L'appareil vient de prouver la possession du secret : pas besoin de
        // repasser par l'écran de vérification immédiatement après coup dans
        // cette même session.
        $request->session()->put('2fa_verified_user_id', $user->id);

        $auditLog->log(
            action: 'two_factor_enabled',
            modelType: $user::class,
            modelId: $user->id,
            comment: 'Double authentification activée par son propriétaire.'
        );

        return redirect()->route('two-factor.show')->with('recoveryCodes', $recoveryCodes);
    }

    public function destroy(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        Gate::authorize('gerer-double-authentification');

        $request->validate(['current_password' => ['required', 'current_password']]);

        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget(['two_factor_pending_secret', '2fa_verified_user_id']);

        $auditLog->log(
            action: 'two_factor_disabled',
            modelType: $user::class,
            modelId: $user->id,
            comment: 'Double authentification désactivée par son propriétaire, après revérification du mot de passe.'
        );

        return redirect()->route('two-factor.show')->with('success', 'Double authentification désactivée.');
    }
}
