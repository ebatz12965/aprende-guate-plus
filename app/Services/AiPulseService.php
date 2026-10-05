<?php

namespace App\Services;

use App\AI\AgentCatalog;
use App\Models\AiRequest;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Datos del tablero de IA a partir de ai_requests (una fila por llamada a un proveedor).
 * Las agrupaciones se hacen en PHP para funcionar igual en MySQL y en SQLite.
 */
class AiPulseService
{
    public const RANGES = [60 => 30, 1440 => 48, 10080 => 42, 43200 => 30];

    private const COLUMNS = ['id', 'user_id', 'agent_slug', 'provider', 'model', 'operation', 'status', 'input_tokens', 'output_tokens', 'total_tokens', 'duration_ms', 'failure_reason', 'created_at'];

    public function summary(int $range, ?string $provider = null): array
    {
        $range = array_key_exists($range, self::RANGES) ? $range : 60;
        $now = now();
        // El límite superior incluye el segundo actual: una solicitud recién registrada ya cuenta.
        $rows = $this->rows($now->copy()->subMinutes($range), $now->copy()->addSecond(), $provider);
        $previous = $this->rows($now->copy()->subMinutes($range * 2), $now->copy()->subMinutes($range), $provider);
        $prices = $this->prices();
        $users = $this->users($rows->pluck('user_id')->filter()->unique()->all());
        $buckets = self::RANGES[$range];

        $cost = fn (Collection $list) => $prices ? round($list->sum(fn ($row) => $this->cost($row, $prices)), 4) : null;
        $done = $rows->whereIn('status', ['completed', 'failed']);
        $okRate = $done->count() ? round($done->where('status', 'completed')->count() / $done->count() * 100, 1) : null;

        return [
            'generated_at' => $now->toIso8601String(),
            'range' => $range,
            'has_prices' => (bool) $prices,
            'kpis' => [
                'tokens' => (int) $rows->sum('total_tokens'),
                'requests' => $rows->count(),
                'cost' => $cost($rows),
                'ok_rate' => $okRate,
                'failed' => $rows->where('status', 'failed')->count(),
                'deltas' => [
                    'tokens' => $this->delta($rows->sum('total_tokens'), $previous->sum('total_tokens')),
                    'requests' => $this->delta($rows->count(), $previous->count()),
                    'cost' => $prices ? $this->delta($cost($rows), $cost($previous)) : null,
                ],
                'spark' => [
                    'tokens' => $this->series($rows, $now, $range, 24, fn ($row) => $row->total_tokens),
                    'requests' => $this->series($rows, $now, $range, 24, fn () => 1),
                    'ok' => $this->series($rows, $now, $range, 24, fn ($row) => $row->status === 'completed' ? 1 : 0),
                ],
            ],
            'providers' => $this->providerTotals($rows),
            'timeline' => $this->timeline($rows, $now, $range, $buckets),
            'users' => $rows->whereNotNull('user_id')->groupBy('user_id')->map(fn ($list, $id) => [
                'id' => (int) $id,
                'name' => $users[$id]['name'] ?? 'Usuario eliminado',
                'role' => $users[$id]['role'] ?? '',
                'requests' => $list->count(),
                'tokens' => (int) $list->sum('total_tokens'),
            ])->sortByDesc('tokens')->take(10)->values()->all(),
            'models' => $rows->groupBy('model')->map(fn ($list, $model) => [
                'id' => $model,
                'provider' => $list->first()->provider,
                'provider_name' => $this->providerName($list->first()->provider),
                'requests' => $list->count(),
                'tokens' => (int) $list->sum('total_tokens'),
                'cost' => $cost($list),
            ])->sortByDesc('tokens')->values()->all(),
            'agents' => $rows->groupBy('agent_slug')->map(fn ($list, $slug) => [
                'id' => $slug,
                'name' => $this->agentName($slug),
                'requests' => $list->count(),
                'tokens' => (int) $list->sum('total_tokens'),
            ])->sortByDesc('tokens')->values()->all(),
            'status' => [
                'ok' => $rows->where('status', 'completed')->count(),
                'failed' => $rows->where('status', 'failed')->count(),
                'processing' => $rows->where('status', 'processing')->count(),
                'reasons' => $rows->where('status', 'failed')->groupBy(fn ($row) => $this->reason($row->failure_reason))
                    ->map(fn ($list, $reason) => ['reason' => $reason, 'count' => $list->count()])->sortByDesc('count')->values()->all(),
            ],
            'heat' => $this->heat($provider),
            'feed' => $this->feed($provider),
        ];
    }

    /** Detalle de un usuario, modelo, agente, proveedor, solicitud o de los fallos del periodo. */
    public function detail(string $type, string $key, int $range, ?string $provider = null): ?array
    {
        $range = array_key_exists($range, self::RANGES) ? $range : 60;
        $now = now();
        if ($type === 'request') {
            $row = AiRequest::query()->find((int) $key, self::COLUMNS);
            if (! $row) {
                return null;
            }
            $user = $row->user_id ? ($this->users([$row->user_id])[$row->user_id] ?? null) : null;

            return [
                'kind' => 'Solicitud del '.$row->created_at->locale('es')->translatedFormat('j \d\e F, g:i:s a'),
                'title' => $this->agentName($row->agent_slug),
                'stats' => [['Entrada', number_format($row->input_tokens)], ['Salida', number_format($row->output_tokens)], ['Duración', $row->duration_ms ? round($row->duration_ms / 1000, 1).' s' : '—']],
                'list_title' => 'Detalle',
                'list' => array_values(array_filter([
                    ['Usuario', $user ? $user['name'].($user['role'] ? ' · '.$user['role'] : '') : 'Sin usuario (proceso del sistema)'],
                    ['Proveedor', $this->providerName($row->provider)],
                    ['Modelo', $row->model],
                    ['Operación', $row->operation],
                    ['Estado', ['completed' => 'Correcta', 'failed' => 'Con fallo', 'processing' => 'En curso'][$row->status] ?? $row->status],
                    $row->failure_reason ? ['Error', Str::limit($row->failure_reason, 160)] : null,
                    ($prices = $this->prices()) ? ['Costo estimado', $this->money($this->cost($row, $prices))] : null,
                ])),
                'series' => [],
                'recent' => [],
            ];
        }

        // El límite superior incluye el segundo actual: una solicitud recién registrada ya cuenta.
        $rows = $this->rows($now->copy()->subMinutes($range), $now->copy()->addSecond(), $provider);
        $field = ['user' => 'user_id', 'model' => 'model', 'agent' => 'agent_slug', 'provider' => 'provider'][$type] ?? null;
        $list = match (true) {
            $type === 'failures' => $rows->where('status', 'failed'),
            $type === 'overview' => $rows,
            $field !== null => $rows->filter(fn ($row) => (string) $row->{$field} === $key),
            default => null,
        };
        if ($list === null) {
            return null;
        }

        $prices = $this->prices();
        $users = $this->users($list->pluck('user_id')->filter()->unique()->all());
        $title = match ($type) {
            'user' => $users[(int) $key]['name'] ?? 'Usuario',
            'model' => $key,
            'agent' => $this->agentName($key),
            'provider' => $this->providerName($key),
            'failures' => $list->count().' con fallo',
            default => 'Todo el consumo',
        };
        $breakdown = match ($type) {
            'user' => ['Agentes que más usa', $list->groupBy('agent_slug')->map(fn ($group, $slug) => [$this->agentName($slug), (int) $group->sum('total_tokens')])],
            'model', 'agent', 'provider' => ['Usuarios que más lo usan', $list->whereNotNull('user_id')->groupBy('user_id')->map(fn ($group, $id) => [$users[$id]['name'] ?? 'Usuario', (int) $group->sum('total_tokens')])],
            'failures' => ['Causas', $list->groupBy(fn ($row) => $this->reason($row->failure_reason))->map(fn ($group, $reason) => [$reason, $group->count()])],
            default => ['Por proveedor', $list->groupBy('provider')->map(fn ($group, $id) => [$this->providerName($id), (int) $group->sum('total_tokens')])],
        };
        $done = $list->whereIn('status', ['completed', 'failed']);

        return [
            'kind' => ['user' => 'Usuario', 'model' => 'Modelo', 'agent' => 'Agente', 'provider' => 'Proveedor', 'failures' => 'Solicitudes con fallo', 'overview' => 'Resumen del periodo'][$type],
            'title' => $title,
            'stats' => [
                ['Tokens', $this->short($list->sum('total_tokens'))],
                ['Solicitudes', number_format($list->count())],
                ['Costo', $prices ? $this->money($list->sum(fn ($row) => $this->cost($row, $prices))) : '—'],
                ['Entrada / salida', $this->short($list->sum('input_tokens')).' / '.$this->short($list->sum('output_tokens'))],
                ['Correctas', $done->count() ? round($done->where('status', 'completed')->count() / $done->count() * 100).' %' : '—'],
                ['Duración media', $list->avg('duration_ms') ? round($list->avg('duration_ms') / 1000, 1).' s' : '—'],
            ],
            'list_title' => $breakdown[0],
            'list' => $breakdown[1]->sortByDesc(fn ($item) => $item[1])->take(6)->map(fn ($item) => [$item[0], $type === 'failures' ? (string) $item[1] : $this->short($item[1])])->values()->all(),
            'series' => $this->series($list, $now, $range, 24, fn ($row) => $row->total_tokens),
            'recent' => $list->sortByDesc('id')->take(6)->map(fn ($row) => [
                'id' => $row->id,
                'label' => $row->created_at->format('H:i').' · '.$this->agentName($row->agent_slug).' · '.$row->model,
                'value' => $row->status === 'failed' ? 'falló' : $this->short($row->total_tokens),
            ])->values()->all(),
        ];
    }

    /** Precios por millón de tokens (USD) definidos en el orquestador: [modelo => [input, output]]. */
    public function prices(): array
    {
        $decoded = json_decode((string) (PlatformSetting::query()->where('key', 'ai_model_prices')->value('value') ?? ''), true);

        return is_array($decoded) ? array_filter($decoded, fn ($price) => is_array($price) && (($price['input'] ?? 0) > 0 || ($price['output'] ?? 0) > 0)) : [];
    }

    public function savePrices(array $prices): void
    {
        $clean = collect($prices)->mapWithKeys(fn ($price, $model) => [(string) $model => ['input' => round((float) ($price['input'] ?? 0), 4), 'output' => round((float) ($price['output'] ?? 0), 4)]])
            ->filter(fn ($price) => $price['input'] > 0 || $price['output'] > 0)->all();
        PlatformSetting::setString('ai_model_prices', json_encode($clean));
    }

    /** Modelos usados alguna vez o configurados en algún agente, para la tabla de precios. */
    public function knownModels(): array
    {
        $configured = collect(app(AgentCatalog::class)->all())->pluck('model');

        return AiRequest::query()->distinct()->pluck('model')->merge($configured)->merge(array_keys($this->prices()))->filter()->unique()->sort()->values()->all();
    }

    private function rows(Carbon $from, Carbon $to, ?string $provider): Collection
    {
        return AiRequest::query()->select(self::COLUMNS)->where('created_at', '>=', $from)->where('created_at', '<', $to)
            ->when($provider, fn ($query) => $query->where('provider', $provider))->orderBy('id')->get();
    }

    private function feed(?string $provider): array
    {
        $rows = AiRequest::query()->select(self::COLUMNS)->when($provider, fn ($query) => $query->where('provider', $provider))->latest('id')->limit(12)->get();
        $users = $this->users($rows->pluck('user_id')->filter()->unique()->all());

        return $rows->map(fn ($row) => [
            'id' => $row->id,
            'time' => $row->created_at->format('H:i:s'),
            'user' => $row->user_id ? ($users[$row->user_id]['name'] ?? 'Usuario eliminado') : 'Sistema',
            'agent' => $this->agentName($row->agent_slug),
            'model' => $row->model,
            'tokens' => (int) $row->total_tokens,
            'seconds' => $row->duration_ms ? round($row->duration_ms / 1000, 1) : null,
            'status' => $row->status,
            'reason' => $row->status === 'failed' ? $this->reason($row->failure_reason) : null,
        ])->all();
    }

    /** Solicitudes de los últimos 30 días por día de la semana (lunes primero) y hora. */
    private function heat(?string $provider): array
    {
        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        AiRequest::query()->where('created_at', '>=', now()->subDays(30))->when($provider, fn ($query) => $query->where('provider', $provider))
            ->pluck('created_at')->each(function ($date) use (&$grid) {
                $date = Carbon::parse($date);
                $grid[$date->dayOfWeekIso - 1][$date->hour]++;
            });

        return $grid;
    }

    private function timeline(Collection $rows, Carbon $now, int $range, int $count): array
    {
        $size = $range * 60 / $count;
        $start = $now->getTimestamp() - $range * 60;
        $buckets = [];
        for ($i = 0; $i < $count; $i++) {
            $buckets[] = ['t' => Carbon::createFromTimestamp((int) ($start + ($i + 1) * $size))->toIso8601String(), 'by' => []];
        }
        foreach ($rows as $row) {
            $index = min($count - 1, max(0, (int) floor(($row->created_at->getTimestamp() - $start) / $size)));
            $buckets[$index]['by'][$row->provider] = ($buckets[$index]['by'][$row->provider] ?? 0) + (int) $row->total_tokens;
        }

        return $buckets;
    }

    private function series(Collection $rows, Carbon $now, int $range, int $count, callable $value): array
    {
        $size = $range * 60 / $count;
        $start = $now->getTimestamp() - $range * 60;
        $out = array_fill(0, $count, 0);
        foreach ($rows as $row) {
            $index = min($count - 1, max(0, (int) floor(($row->created_at->getTimestamp() - $start) / $size)));
            $out[$index] += $value($row);
        }

        return $out;
    }

    private function providerTotals(Collection $rows): array
    {
        $totals = $rows->groupBy('provider')->map(fn ($list) => (int) $list->sum('total_tokens'));

        return collect(config('ai.providers', []))->keys()->merge($totals->keys())->unique()
            ->map(fn ($id) => ['id' => $id, 'name' => $this->providerName($id), 'tokens' => $totals[$id] ?? 0])->values()->all();
    }

    private function users(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return User::query()->whereIn('id', $ids)->with('roles:id,name')->get(['id', 'name'])->mapWithKeys(fn (User $user) => [$user->id => [
            'name' => $user->name,
            'role' => ['Admin' => 'Administrador', 'Instructor' => 'Mentor'][$user->roles->first()?->name] ?? 'Estudiante',
        ]])->all();
    }

    private function cost(object $row, array $prices): float
    {
        $price = $prices[$row->model] ?? null;

        return $price ? ($row->input_tokens * ($price['input'] ?? 0) + $row->output_tokens * ($price['output'] ?? 0)) / 1_000_000 : 0.0;
    }

    /** Agrupa los mensajes de error de los proveedores en causas que se entienden. */
    public function reason(?string $message): string
    {
        $text = Str::lower((string) $message);

        return match (true) {
            $text === '' => 'Error sin detalle',
            Str::contains($text, ['timed out', 'timeout', 'curl error 28', 'tiempo de espera']) => 'Tiempo de espera agotado',
            Str::contains($text, ['presupuesto']) => 'Presupuesto de tokens agotado',
            Str::contains($text, ['credencial', 'api key', 'unauthorized', 'incorrect api key']) => 'Sin credencial válida',
            Str::contains($text, ['rate limit', '429', 'quota']) => 'Límite del proveedor alcanzado',
            Str::contains($text, ['unsupported', 'invalid', 'not supported', 'bad request']) => 'Solicitud rechazada por el proveedor',
            Str::contains($text, ['could not resolve', 'connection', 'conexión']) => 'Sin conexión con el proveedor',
            default => 'Otro error',
        };
    }

    private function delta(float|int|null $current, float|int|null $previous): ?int
    {
        return $previous ? (int) round(($current - $previous) / $previous * 100) : null;
    }

    private function agentName(?string $slug): string
    {
        return config("ai.agents.{$slug}.name") ?? Str::headline((string) $slug);
    }

    private function providerName(?string $id): string
    {
        return config("ai.providers.{$id}.name") ?? Str::headline((string) $id);
    }

    private function short(float|int $value): string
    {
        return match (true) {
            $value >= 1_000_000 => round($value / 1_000_000, $value >= 10_000_000 ? 0 : 1).' M',
            $value >= 1_000 => round($value / 1_000, $value >= 10_000 ? 0 : 1).' k',
            default => (string) (int) round($value),
        };
    }

    private function money(float $value): string
    {
        return 'US$ '.number_format($value, $value < 10 ? 2 : 0);
    }
}
