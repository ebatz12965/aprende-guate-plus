<?php

namespace App\Services;

use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Hash;

class TwoFactorAuthenticationService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        $bytes = random_bytes(20);
        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }

            $secret .= self::ALPHABET[bindec($chunk)];
        }

        return rtrim($secret, '=');
    }

    public function provisioningUri(User $user, string $secret): string
    {
        $issuer = config('app.name', 'Aprende Guate');
        $label = rawurlencode($issuer.':'.$user->email);

        return 'otpauth://totp/'.$label.'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    public function qrCodeDataUri(User $user, string $secret): string
    {
        $result = (new Builder(
            writer: new PngWriter,
            validateResult: false,
            data: $this->provisioningUri($user, $secret),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 280,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build();

        return $result->getDataUri();
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->matchingCounter($secret, $code) !== null;
    }

    /** Periodo de 30 s al que corresponde el código (se acepta uno antes y uno después por desfase de reloj). */
    public function matchingCounter(string $secret, string $code): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $counter = intdiv(time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals($this->codeForCounter($secret, $counter + $offset), $code)) {
                return $counter + $offset;
            }
        }

        return null;
    }

    /** Código de la aplicación de la cuenta; cada código sirve una sola vez (alguien que lo vea no puede reutilizarlo). */
    public function verifyForUser(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }
        $counter = $this->matchingCounter($user->two_factor_secret, $code);
        if ($counter === null || ($user->two_factor_last_counter !== null && $counter <= $user->two_factor_last_counter)) {
            return false;
        }
        $user->forceFill(['two_factor_last_counter' => $counter])->save();

        return true;
    }

    public function recoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => strtoupper(substr(bin2hex(random_bytes(4)), 0, 4).'-'.substr(bin2hex(random_bytes(4)), 0, 4)))
            ->all();
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = strtoupper(trim($code));

        foreach ($codes as $index => $hash) {
            if (Hash::check($normalized, $hash)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    private function codeForCounter(string $secret, int $counter): string
    {
        $hash = hash_hmac('sha1', pack('N2', 0, $counter), $this->decodeSecret($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function decodeSecret(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $bits = '';

        foreach (str_split($secret) as $character) {
            $position = strpos(self::ALPHABET, $character);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }
}
