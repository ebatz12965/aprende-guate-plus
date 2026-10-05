<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiPulseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Tablero de IA del panel de administración: consumo en vivo por usuario, modelo, agente y proveedor. */
class AiPulseController extends Controller
{
    public function index(AiPulseService $pulse): View
    {
        return view('admin.ai-pulse.index', [
            'initial' => $pulse->summary(60),
            'providers' => collect(config('ai.providers', []))->map(fn ($provider, $id) => ['id' => $id, 'name' => $provider['name'] ?? $id])->values(),
        ]);
    }

    public function data(Request $request, AiPulseService $pulse): JsonResponse
    {
        $data = $request->validate(['range' => ['nullable', 'integer'], 'provider' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('ai.providers', [])))]]);

        return response()->json($pulse->summary((int) ($data['range'] ?? 60), $data['provider'] ?? null));
    }

    public function detail(Request $request, AiPulseService $pulse): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:user,model,agent,provider,request,failures,overview'],
            'key' => ['nullable', 'string', 'max:160'],
            'range' => ['nullable', 'integer'],
            'provider' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('ai.providers', [])))],
        ]);
        $detail = $pulse->detail($data['type'], (string) ($data['key'] ?? ''), (int) ($data['range'] ?? 60), $data['provider'] ?? null);

        return $detail ? response()->json($detail) : response()->json(['message' => 'No encontramos ese dato.'], 404);
    }

    public function savePrices(Request $request, AiPulseService $pulse): RedirectResponse
    {
        // Cada fila lleva el modelo como campo: los identificadores tienen puntos y no sirven como clave del arreglo.
        $data = $request->validate([
            'prices' => ['array'],
            'prices.*.model' => ['required', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:\/-]+$/'],
            'prices.*.input' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'prices.*.output' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ]);
        $pulse->savePrices(collect($data['prices'] ?? [])->mapWithKeys(fn ($row) => [$row['model'] => ['input' => $row['input'] ?? 0, 'output' => $row['output'] ?? 0]])->all());

        return redirect()->route('admin.ai-orchestrator.index')->with('success', 'Precios por modelo guardados. El tablero de IA ya calcula el costo estimado con ellos.');
    }
}
