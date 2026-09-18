<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTokenUsage;
use App\Models\PlatformSetting;
use Illuminate\View\View;

class AiUsageController extends Controller
{
    public function index(): View
    {
        $budget = PlatformSetting::aiTokenBudget();
        $totalConsumed = (int) AiTokenUsage::sum('total_tokens');
        $periodStart = now()->subDays(29)->startOfDay();
        $periodConsumed = (int) AiTokenUsage::where('created_at', '>=', $periodStart)->sum('total_tokens');
        $requests = AiTokenUsage::count();
        $successfulRequests = AiTokenUsage::where('successful', true)->count();

        $dailyTokens = AiTokenUsage::query()
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('DATE(created_at) as date, SUM(total_tokens) as tokens')
            ->groupBy('date')
            ->pluck('tokens', 'date');

        $dailyUsage = collect(range(0, 29))->map(function (int $offset) use ($periodStart, $dailyTokens) {
            $date = $periodStart->copy()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'label' => $date->format('d/m'),
                'tokens' => (int) ($dailyTokens[$date->toDateString()] ?? 0),
            ];
        });

        $peak = $dailyUsage->sortByDesc('tokens')->first();
        $topConsumers = AiTokenUsage::query()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, SUM(total_tokens) as tokens, COUNT(*) as requests')
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderByDesc('tokens')
            ->limit(5)
            ->get();
        $operations = AiTokenUsage::query()
            ->selectRaw('operation, SUM(total_tokens) as tokens, COUNT(*) as requests')
            ->groupBy('operation')
            ->orderByDesc('tokens')
            ->get();
        $recentUsages = AiTokenUsage::with('user:id,name,email')->latest()->limit(10)->get();

        return view('admin.ai-usage.index', [
            'budget' => $budget,
            'totalConsumed' => $totalConsumed,
            'periodConsumed' => $periodConsumed,
            'remaining' => $budget > 0 ? max(0, $budget - $totalConsumed) : null,
            'usageRate' => $budget > 0 ? min(100, round(($totalConsumed / $budget) * 100, 1)) : null,
            'requests' => $requests,
            'successfulRequests' => $successfulRequests,
            'dailyUsage' => $dailyUsage,
            'peak' => $peak,
            'topConsumers' => $topConsumers,
            'operations' => $operations,
            'recentUsages' => $recentUsages,
        ]);
    }
}
