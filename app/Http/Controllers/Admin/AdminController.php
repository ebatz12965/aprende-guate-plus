<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTokenUsage;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $totalUsers = User::count();
        $latestUsers = User::query()->latest()->take(5)->get(['id', 'name', 'email', 'created_at', 'email_verified_at']);
        $tokenBudget = PlatformSetting::aiTokenBudget();
        $tokensUsed = (int) AiTokenUsage::sum('total_tokens');
        $tokensAvailable = $tokenBudget > 0 ? max(0, $tokenBudget - $tokensUsed) : null;
        $usageRequests = AiTokenUsage::count();
        $topConsumers = AiTokenUsage::query()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, SUM(total_tokens) as total_tokens, COUNT(*) as requests')
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderByDesc('total_tokens')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('totalUsers', 'latestUsers', 'tokenBudget', 'tokensUsed', 'tokensAvailable', 'usageRequests', 'topConsumers'));
    }
}
