<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'categoria' => ['nullable', Rule::in(array_keys(Audit::LOGS))],
            'quien' => ['nullable', 'string', 'max:120'],
            'cuenta' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $activities = Activity::with(['causer', 'subject'])
            ->when($filters['categoria'] ?? null, fn ($query, $log) => $query->where('log_name', $log))
            ->when($filters['quien'] ?? null, fn ($query, $q) => $query->whereHasMorph('causer', [User::class],
                fn ($inner) => $inner->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when($filters['cuenta'] ?? null, fn ($query, $id) => $query->where('subject_type', (new User)->getMorphClass())->where('subject_id', $id))
            ->when($filters['desde'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['hasta'] ?? null, fn ($query, $date) => $query->where('created_at', '<=', Carbon::parse($date)->endOfDay()))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'activities' => $activities,
            'filters' => $filters,
            'account' => isset($filters['cuenta']) ? User::find($filters['cuenta']) : null,
        ]);
    }
}
