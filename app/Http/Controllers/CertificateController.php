<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Muestra la página pública de un certificado.
     */
    public function show(Certificate $certificate)
    {
        $certificate->load('user', 'course');
        return view('certificate.show', compact('certificate'));
    }
}
