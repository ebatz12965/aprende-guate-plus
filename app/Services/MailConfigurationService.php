<?php

namespace App\Services;

use App\Models\PlatformSetting;

class MailConfigurationService
{
    public function apply(): void
    {
        $mail = PlatformSetting::mailSettings();

        if (! $mail['enabled'] || blank($mail['host']) || blank($mail['from_address'])) {
            return;
        }

        $scheme = $mail['encryption'] === 'ssl' ? 'smtps' : 'smtp';

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $mail['host'],
            'mail.mailers.smtp.port' => $mail['port'],
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.auto_tls' => $mail['encryption'] !== 'none',
            'mail.mailers.smtp.require_tls' => $mail['encryption'] === 'tls',
            'mail.mailers.smtp.username' => $mail['username'] ?: null,
            'mail.mailers.smtp.password' => $mail['password'] ?: null,
            'mail.from.address' => $mail['from_address'],
            'mail.from.name' => $mail['from_name'] ?: config('app.name'),
        ]);
    }
}
