{{-- Detalle legible de una entrada de la bitácora: "campo: antes → después" o "campo: valor". --}}
@php($properties = $activity->properties?->except(['attributes', 'old'])->all() ?? [])
@if ($properties)
    <dl class="mt-1.5 grid gap-x-3 gap-y-0.5 text-sm text-slate-600 sm:grid-cols-[auto_1fr]">
        @foreach ($properties as $key => $value)
            <dt class="font-semibold text-slate-500">{{ Str::ucfirst($key) }}</dt>
            <dd class="min-w-0 break-words">
                @if (is_array($value) && array_key_exists('antes', $value))
                    <span class="line-through decoration-slate-400">{{ $value['antes'] }}</span> → <span class="font-semibold text-slate-800">{{ $value['después'] ?? '' }}</span>
                @elseif (is_array($value))
                    {{ collect($value)->map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v)->implode(', ') ?: '—' }}
                @elseif (is_bool($value))
                    {{ $value ? 'Sí' : 'No' }}
                @else
                    {{ $value }}
                @endif
            </dd>
        @endforeach
    </dl>
@endif
