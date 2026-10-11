<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMailSettingsRequest;
use App\Mail\MailSettingsTestMail;
use App\Models\PlatformSetting;
use App\Services\MailConfigurationService;
use App\Support\Audit;
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

            Mail::to($data['test_recipient'])->send(new MailSettingsTestMail($request->user()->name, (string) $settings['host']));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'No se pudo enviar el correo de prueba. Revisa host, puerto, cifrado, usuario y contraseña de aplicación.',
            );
        }

        return back()->with('success', 'Correo de prueba enviado a '.$data['test_recipient'].'. Revisa también la carpeta de spam.');
    }

    public function update(UpdateMailSettingsRequest $request, MailConfigurationService $mailConfiguration): RedirectResponse
    {
        $data = $request->validated();

        PlatformSetting::setString('mail_enabled', $request->boolean('enabled') ? '1' : '0');
        foreach (['host', 'port', 'encryption', 'username', 'from_address', 'from_name'] as $key) {
            PlatformSetting::setString('mail_'.$key, (string) ($data[$key] ?? ''));
        }

        if ($request->filled('password')) {
            PlatformSetting::setEncrypted('mail_password', $data['password']);
        }

        $mailConfiguration->apply();
        // Sin valores: el usuario SMTP y la contraseña no deben quedar en la bitácora.
        Audit::log('configuracion', 'Actualizó el correo saliente', null, ['activo' => $request->boolean('enabled'), 'contraseña' => $request->filled('password') ? 'cambiada' : 'sin cambios']);

        return back()->with('success', 'La configuración de correo saliente fue actualizada. La contraseña de aplicación permanece cifrada.');
    }
}
