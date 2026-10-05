<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTokenUsage;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\AiPulseService;
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
        $aiToday = app(AiPulseService::class)->summary(1440);

        return view('admin.dashboard', compact('totalUsers', 'latestUsers', 'tokenBudget', 'tokensUsed', 'tokensAvailable', 'usageRequests', 'aiToday'));
    }
}
