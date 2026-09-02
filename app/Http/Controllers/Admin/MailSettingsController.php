<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\MailConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MailSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.mail-settings.edit', ['settings' => PlatformSetting::mailSettings()]);
    }

    public function sendTest(Request $request, MailConfigurationService $mailConfiguration): RedirectResponse
    {
        $settings = PlatformSetting::mailSettings();

        if (! $settings['enabled'] || blank($settings['host']) || blank($settings['from_address'])) {
            return back()->with('error', 'Guarda y activa una configuración SMTP completa antes de enviar una prueba.');
        }

        $data = $request->validate([
            'test_recipient' => ['required', 'email:rfc', 'max:255'],
        ]);

        try {
            $mailConfiguration->apply();

            Mail::raw(
                'Este es un correo de prueba de Aprende Guate. Si lo recibiste, la configuración SMTP administrada está funcionando correctamente.',
                function ($message) use ($data): void {
                    $message->to($data['test_recipient'])->subject('Prueba de correo · Aprende Guate');
                },
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'No se pudo enviar el correo de prueba. Revisa host, puerto, cifrado, usuario y contraseña de aplicación.',
            );
        }

        return back()->with('success', 'Correo de prueba enviado a '.$data['test_recipient'].'. Revisa también la carpeta de spam.');
    }

    public function update(Request $request, MailConfigurationService $mailConfiguration): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'host' => ['required_if:enabled,1', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:enabled,1', 'nullable', 'integer', 'between:1,65535'],
            'encryption' => ['required', 'in:none,tls,ssl'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'from_address' => ['required_if:enabled,1', 'nullable', 'email:rfc', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:120'],
        ]);

        PlatformSetting::setString('mail_enabled', $request->boolean('enabled') ? '1' : '0');
        foreach (['host', 'port', 'encryption', 'username', 'from_address', 'from_name'] as $key) {
            PlatformSetting::setString('mail_'.$key, (string) ($data[$key] ?? ''));
        }

        if ($request->filled('password')) {
            PlatformSetting::setEncrypted('mail_password', $data['password']);
        }

        $mailConfiguration->apply();

        return back()->with('success', 'La configuración de correo saliente fue actualizada. La contraseña de aplicación permanece cifrada.');
    }
}
