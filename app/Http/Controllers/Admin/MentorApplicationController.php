<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MentorApplication;
use App\Models\MentorProfile;
use App\Notifications\PlatformAlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MentorApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->input('status', 'pending_review'), ['pending_review', 'approved', 'rejected'], true) ? $request->input('status', 'pending_review') : 'pending_review';
        $applications = MentorApplication::with(['user', 'reviewer'])->where('status', $status)->latest()->paginate(15)->withQueryString();
        return view('admin.mentor-applications.index', compact('applications', 'status'));
    }

    public function show(MentorApplication $application): View { return view('admin.mentor-applications.show', compact('application')); }

    public function approve(Request $request, MentorApplication $application): RedirectResponse
    {
        abort_unless($application->status === 'pending_review', 422);
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:3000']]);
        // La aprobación agrega capacidades de mentor sin retirar su experiencia como estudiante o administrador.
        $application->user->assignRole('Instructor');
        MentorProfile::updateOrCreate(['user_id' => $application->user_id], ['headline' => $application->professional_title, 'specialties' => $application->specialties, 'about' => $application->professional_background, 'session_duration_minutes' => $application->session_duration_minutes, 'availability' => $application->availability ?? []]);
        $application->update(['status' => 'approved', 'admin_notes' => $data['admin_notes'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $application->user->notify(new PlatformAlertNotification(category: 'platform', title: 'Tu solicitud de mentor fue aprobada', body: 'Tu perfil de mentor ya está habilitado. Puedes comenzar a configurar tu disponibilidad y recibir solicitudes.', url: route('mentor.dashboard'), actionLabel: 'Abrir panel mentor'));
        return redirect()->route('admin.mentor-applications.index')->with('success', 'Solicitud aprobada. El usuario ya puede operar como mentor.');
    }

    public function reject(Request $request, MentorApplication $application): RedirectResponse
    {
        abort_unless($application->status === 'pending_review', 422);
        $data = $request->validate(['admin_notes' => ['required', 'string', 'min:10', 'max:3000']]);
        $application->update(['status' => 'rejected', 'admin_notes' => $data['admin_notes'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $application->user->notify(new PlatformAlertNotification(category: 'platform', title: 'Tu solicitud de mentor fue revisada', body: 'El administrador registró observaciones sobre tu solicitud. Consulta el detalle desde tu configuración.', url: route('mentor-application.create'), actionLabel: 'Ver solicitud'));
        return redirect()->route('admin.mentor-applications.index')->with('success', 'Solicitud rechazada y motivo registrado.');
    }
}
