<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\LibraryTopic;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Audio de narración de un paso del extracto animado (contenido de la biblioteca, igual para todas las personas). */
class LibraryNarrationController extends Controller
{
    public function __invoke(LibraryTopic $libraryTopic, int $step): BinaryFileResponse
    {
        abort_unless((bool) data_get($libraryTopic->walkthrough, 'steps.'.($step - 1).'.audio'), 404);
        $path = "library/narration/{$libraryTopic->id}/{$step}.mp3";
        abort_unless(Storage::disk('local')->exists($path), 404);

        // El audio no cambia: el navegador puede guardarlo una semana.
        return response()->file(Storage::disk('local')->path($path), ['Content-Type' => 'audio/mpeg', 'Cache-Control' => 'private, max-age=604800']);
    }
}
