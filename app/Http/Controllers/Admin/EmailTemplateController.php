<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\PlatformSetting;
use App\Services\MailConfigurationService;
use App\Support\Audit;
use App\Support\EmailTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    private const FIELDS = ['subject', 'heading', 'body', 'action_label', 'closing'];

    public function index(): View
    {
        return view('admin.email-templates.index', [
            'definitions' => EmailTemplates::definitions(),
            'custom' => EmailTemplate::query()->with('editor:id,name')->get()->keyBy('key'),
        ]);
    }

    public function edit(string $key): View
    {
        abort_unless(EmailTemplates::exists($key), 404);

        return view('admin.email-templates.edit', [
            'key' => $key,
            'definition' => EmailTemplates::definition($key),
            'fields' => EmailTemplates::fields($key),
            'custom' => EmailTemplate::query()->with('editor:id,name')->where('key', $key)->first(),
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);
        $data = $this->validated($request, $key);

        EmailTemplate::query()->updateOrCreate(['key' => $key], [...$data, 'updated_by' => $request->user()->id]);
        Audit::log('configuracion', 'Editó la plantilla de correo «'.EmailTemplates::definition($key)['name'].'»', null, ['plantilla' => $key]);

        return redirect()->route('admin.email-templates.edit', $key)->with('success', 'Plantilla guardada. Los próximos correos usarán este texto.');
    }

    public function destroy(string $key): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);

        EmailTemplate::query()->where('key', $key)->delete();
        Audit::log('configuracion', 'Restauró la plantilla de correo original «'.EmailTemplates::definition($key)['name'].'»', null, ['plantilla' => $key]);

        return redirect()->route('admin.email-templates.edit', $key)->with('success', 'Se restauró el texto original de la plantilla.');
    }

    /** Vista previa con datos de ejemplo; si llegan campos del formulario, muestra el borrador sin guardarlo. */
    public function preview(Request $request, string $key): Response
    {
        abort_unless(EmailTemplates::exists($key), 404);

        $fields = EmailTemplates::fields($key);
        if ($request->has('subject')) {
            foreach (self::FIELDS as $field) {
                $fields[$field] = mb_substr((string) $request->input($field, ''), 0, 5000);
            }
        }

        return response((new TemplatedMail($this->sample($key, $fields)))->render())
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function sendTest(Request $request, string $key, MailConfigurationService $mailConfiguration): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);
        $data = $request->validate(['test_recipient' => ['required', 'email:rfc', 'max:255']]);

        $settings = PlatformSetting::mailSettings();
        if (! $settings['enabled'] || blank($settings['host']) || blank($settings['from_address'])) {
            return back()->with('error', 'Primero guarda y activa el correo saliente (SMTP) para enviar pruebas.');
        }

        $email = $this->sample($key, EmailTemplates::fields($key));
        $email['subject'] = '[Prueba] '.$email['subject'];

        try {
            $mailConfiguration->apply();
            Mail::to($data['test_recipient'])->send(new TemplatedMail($email));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo enviar la prueba. Revisa la configuración del correo saliente.');
        }

        return back()->with('success', 'Prueba enviada a '.$data['test_recipient'].' con datos de ejemplo y la versión guardada de la plantilla.');
    }

    private function sample(string $key, array $fields): array
    {
        return EmailTemplates::compose($fields, EmailTemplates::sampleValues($key), config('app.url'), EmailTemplates::sampleTranscript($key));
    }

    private function validated(Request $request, string $key): array
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'heading' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'action_label' => ['nullable', 'string', 'max:60'],
            'closing' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'subject' => 'asunto',
            'heading' => 'título',
            'body' => 'mensaje',
            'action_label' => 'texto del botón',
            'closing' => 'cierre',
        ]);

        // Un marcador mal escrito saldría tal cual en el correo; mejor avisarlo al guardar.
        $allowed = array_keys(EmailTemplates::definition($key)['placeholders']);
        $errors = [];
        foreach (self::FIELDS as $field) {
            $unknown = EmailTemplates::placeholdersIn((string) ($data[$field] ?? ''))->diff($allowed);
            if ($unknown->isNotEmpty()) {
                $errors[$field] = 'Marcador desconocido: '.$unknown->map(fn ($name) => '{'.$name.'}')->implode(', ').'. Usa solo los de la lista.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return collect(self::FIELDS)->mapWithKeys(fn ($field) => [$field => trim((string) ($data[$field] ?? ''))])->all();
    }
}
