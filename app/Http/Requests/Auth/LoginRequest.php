<?php

namespace App\Http\Requests\Auth;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'string'],
            'matricule' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->input('matricule') ?: $this->input('email');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'matricule';

        $user = $login ? User::where($field, $login)->first() : null;

        // Le personnel remet parfois à l'élève/au parent le matricule imprimé sur le
        // dossier d'inscription (Registration::matricule, ex. EDU-26-000001) plutôt que
        // le matricule personnel de l'élève (ex. ELE-260001) — deux numérotations
        // distinctes générées par StudentEnrollmentService, déjà source de confusion
        // documentée côté réinscription (RegistrationController::reenrollSearch). On
        // retombe sur la première pour rester tolérant à la confusion courante, sans
        // jamais authentifier directement dessus (la connexion reste bien sur le compte
        // User et son mot de passe).
        if (! $user && $field === 'matricule' && $login) {
            $registration = Registration::where('matricule', $login)->first();
            $user = $registration?->user;
        }

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                $field => 'Ce compte est désactivé. Veuillez contacter un administrateur.',
            ]);
        }

        // Authentifier via l'identifiant réel du compte résolu (son propre matricule/email),
        // jamais directement sur l'input brut : celui-ci peut être un matricule de dossier
        // d'inscription (cas ci-dessus) qui ne correspond à aucune colonne de `users`.
        $credentials = $user
            ? [$field => $user->{$field}, 'password' => $this->input('password')]
            : [$field => $login, 'password' => $this->input('password')];

        if (! $login || ! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                $field => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'matricule' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return strtolower((string) ($this->input('matricule') ?: $this->input('email'))).'|'.$this->ip();
    }
}
