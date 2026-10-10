<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\LearningPath;
use App\Services\Learning\CertificateService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Mis certificados y rutas completadas; descarga en PDF y verificación pública por código. */
class CertificateController extends Controller
{
    public function index(Request $request, CertificateService $certificates): View
    {
        $user = $request->user();
        // Las rutas completadas antes de existir los certificados reciben el suyo al entrar aquí.
        $certificates->issueMissing($user->id);
        $completed = LearningPath::where('user_id', $user->id)->where('status', 'completed')->with('certificate')->withCount('items')
            ->orderByDesc('completed_at')->orderByDesc('updated_at')->get();

        return view('certificates.index', [
            'paths' => $completed,
            'previews' => $completed->map(fn (LearningPath $path) => $path->certificate)->filter()->mapWithKeys(fn (Certificate $certificate) => [$certificate->id => $certificates->viewData($certificate)]),
            'activePath' => LearningPath::where('user_id', $user->id)->where('status', 'active')->latest()->first(),
        ]);
    }

    public function show(Request $request, Certificate $certificate, CertificateService $certificates): View
    {
        $this->ensureOwner($request, $certificate);

        return view('certificates.show', $certificates->viewData($certificate));
    }

    public function download(Request $request, Certificate $certificate, CertificateService $certificates): Response
    {
        $this->ensureOwner($request, $certificate);

        return $certificates->pdf($certificate);
    }

    /** Página pública: cualquier empleador puede confirmar que el certificado es auténtico. */
    public function verify(Certificate $certificate, CertificateService $certificates): View
    {
        return view('certificates.verify', $certificates->viewData($certificate));
    }

    private function ensureOwner(Request $request, Certificate $certificate): void
    {
        abort_unless($certificate->user_id === $request->user()->id, 404);
    }
}
