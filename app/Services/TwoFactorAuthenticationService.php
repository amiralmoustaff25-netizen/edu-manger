<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * TOTP (RFC 6238) autonome, compatible Google Authenticator/Authy/1Password —
 * sans dépendance externe. Le projet n'a pas d'extension GD disponible dans cet
 * environnement (voir composer.json : phpoffice/phpspreadsheet est déjà bloqué
 * par son absence), ce qui empêche l'installation de la plupart des paquets
 * TOTP usuels (ils embarquent souvent un générateur de QR code qui en dépend
 * transitivement) sans réorganiser des dépendances hors du périmètre de cette
 * mission. L'algorithme lui-même (HMAC-SHA1 + troncature dynamique, RFC 4226/
 * 6238) est simple et stable — pas de surface de risque cryptographique propre
 * à réinventer au-delà de ce que les bibliothèques font elles-mêmes.
 */
class TwoFactorAuthenticationService
{
    private const SECRET_LENGTH = 20; // 160 bits, recommandation RFC 4226

    private const CODE_DIGITS = 6;

    private const PERIOD_SECONDS = 30;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecretKey(): string
    {
        return $this->base32Encode(random_bytes(self::SECRET_LENGTH));
    }

    /**
     * URI otpauth:// standard — la plupart des apps d'authentification savent le
     * scanner sous forme de QR code (généré côté client si besoin) ; on fournit
     * en complément la clé en clair pour la saisie manuelle (voir la vue), donc
     * l'absence de génération d'image QR côté serveur n'est pas bloquante.
     */
    public function getProvisioningUri(User $user, string $secret): string
    {
        $issuer = rawurlencode(config('app.name', 'EduManager'));
        $label = rawurlencode($issuer.':'.$user->email);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=".self::CODE_DIGITS.'&period='.self::PERIOD_SECONDS;
    }

    /**
     * Vérifie un code TOTP à 6 chiffres avec une tolérance de ±1 pas (30s) pour
     * absorber un léger décalage d'horloge entre le serveur et l'appareil de
     * l'utilisateur, comme le font toutes les implémentations usuelles.
     */
    public function verify(string $secret, ?string $code): bool
    {
        if (! $code || ! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $currentWindow = (int) floor(time() / self::PERIOD_SECONDS);

        for ($drift = -1; $drift <= 1; $drift++) {
            if (hash_equals($this->generateCode($secret, $currentWindow + $drift), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::lower(Str::random(4)).'-'.Str::lower(Str::random(4)))
            ->all();
    }

    private function generateCode(string $secret, int $timeWindow): string
    {
        $key = $this->base32Decode($secret);
        $timeBytes = pack('N*', 0, $timeWindow); // 8 octets big-endian

        $hash = hash_hmac('sha1', $timeBytes, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $code = $truncated % (10 ** self::CODE_DIGITS);

        return str_pad((string) $code, self::CODE_DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $binary): string
    {
        $binaryString = '';
        foreach (str_split($binary) as $byte) {
            $binaryString .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($binaryString, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper((string) preg_replace('/[^A-Z2-7]/i', '', $encoded));

        $binaryString = '';
        foreach (str_split($encoded) as $char) {
            $position = strpos(self::BASE32_ALPHABET, $char);
            if ($position === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        foreach (str_split($binaryString, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr(bindec($byte));
            }
        }

        return $binary;
    }
}
