<?php

namespace App\Http\Controllers;

use App\Services\PlatformGuideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformGuideController extends Controller
{
    public function ask(Request $request, PlatformGuideService $guide): JsonResponse
    {
        $data = $request->validate(['question' => ['required', 'string', 'max:500']]);

        return response()->json($guide->reply($data['question'], $request->user()->id));
    }
}
