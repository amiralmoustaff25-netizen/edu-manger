<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Étape de vérification du code TOTP après une connexion réussie mais tant
 * que la double authentification n'a pas encore été validée pour cette
 * session — voir App\Http\Middleware\RequireTwoFactorVerification, qui
 * redirige ici toute requête d'un utilisateur 2FA-activé qui n'est pas encore
 * passé par cette étape.
 */
class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! Auth::check() || ! Auth::user()->two_factor_confirmed_at) {
            return redirect()->route('login');
        }

        return view('auth.two-factor.challenge');
    }

    public function store(Request $request, TwoFactorAuthenticationService $totp): RedirectResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->two_factor_confirmed_at) {
            return redirect()->route('login');
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        if ($totp->verify($user->two_factor_secret, $validated['code'])) {
            $request->session()->put('2fa_verified_user_id', $user->id);

            return redirect()->intended(route('dashboard'));
        }

        // Un code de secours consomme immédiatement l'entrée correspondante (usage
        // unique) : si l'utilisateur a perdu son appareil, chaque code n'aide
        // qu'une fois, ce qui l'incite à désactiver/réactiver proprement ensuite.
        if ($this->consumeRecoveryCode($user, $validated['code'])) {
            $request->session()->put('2fa_verified_user_id', $user->id);

            return redirect()->intended(route('dashboard'))
                ->with('warning', 'Connexion via un code de secours. Pensez à régénérer vos codes depuis les paramètres de double authentification.');
        }

        return back()->withErrors(['code' => 'Code invalide.']);
    }

    private function consumeRecoveryCode($user, string $submitted): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = Str::lower(trim($submitted));

        if (! in_array($normalized, $codes, true)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_diff($codes, [$normalized])),
        ])->save();

        return true;
    }
}
