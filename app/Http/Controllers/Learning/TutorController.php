<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\AiTutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorController extends Controller
{
    public function show(Request $request): View
    {
        return view('adaptive.tutor', ['conversation' => $this->conversationFor($request)]);
    }

    public function sendMessage(Request $request, AiTutorService $tutor): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:600']]);
        $conversation = $this->conversationFor($request);
        $conversation->messages()->create(['role' => 'user', 'content' => $data['message']]);
        $history = $conversation->messages()->latest()->take(6)->get()->reverse()->map(fn ($message) => ['role' => $message->role, 'content' => $message->content])->values()->all();
        $reply = $tutor->reply($history, $request->user()->name, $request->user()->id);
        $conversation->messages()->create(['role' => 'assistant', 'content' => $reply]);

        return response()->json(['reply' => $reply]);
    }

    private function conversationFor(Request $request): AiConversation
    {
        return AiConversation::firstOrCreate(['user_id' => $request->user()->id, 'title' => 'Tutor IA']);
    }
}
