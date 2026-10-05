@extends('layouts.platform')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    @php($assessment = data_get($attempt->skill_results, 'assessment'))
    @if ($assessment && count($assessment['skills'] ?? []))
        <section class="learning-panel">
            <h1 class="text-balance text-2xl font-extrabold text-slate-900">Tu nivel de partida: {{ $assessment['level'] }}</h1>
            <p class="mt-2 leading-7 text-slate-600">Obtuviste {{ $assessment['score'] }} de 100 en las habilidades que exige tu meta. Tu ruta empieza por las que tienen menor puntaje y no repite lo que ya dominas.</p>
            <ul class="mt-5 space-y-4">
                @foreach (collect($assessment['skills'])->sortBy('score') as $skill)
                    <li>
                        <div class="flex items-baseline justify-between gap-3 text-sm"><span class="font-bold text-slate-900">{{ $skill['name'] }}</span><span class="shrink-0 tabular-nums text-slate-600">{{ $skill['level'] }} · {{ $skill['score'] }} %</span></div>
                        <div class="mt-1.5 h-2 rounded-full bg-slate-100" role="progressbar" aria-label="{{ $skill['name'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $skill['score'] }}"><div class="h-full rounded-full bg-blue-500" style="width: {{ max(2, $skill['score']) }}%"></div></div>
                        @if (($skill['method'] ?? '') === 'knowledge')
                            <p class="mt-1 text-xs text-slate-500">{{ $skill['correct'] }} de {{ $skill['total'] }} correctas</p>
                        @else
                            <p class="mt-1 text-xs text-slate-500">Según tu autoevaluación</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
    <section class="rounded-3xl border border-blue-100 bg-white p-8 text-center shadow-sm" @if($attempt->status === 'generating') x-data x-init="setTimeout(() => window.location.reload(), 4000)" @endif>
        @if($attempt->status === 'generating')
            <span class="ag-soft-pulse ag-brand-loading mx-auto grid h-14 w-14 place-items-center rounded-2xl p-2 text-2xl text-white"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="h-full w-full rounded-xl object-cover"><span class="sr-only">Cargando</span></span>
            <h2 class="mt-6 text-2xl font-black text-slate-900">Estamos organizando tu ruta personalizada</h2>
            <p class="mx-auto mt-3 max-w-lg leading-7 text-slate-600">Puedes cerrar esta pantalla o seguir navegando. Te mostraremos la ruta apenas finalice la generación.</p>
            <p class="ag-soft-pulse mt-5 text-sm font-semibold text-blue-700">Esta página se actualiza automáticamente.</p>
        @elseif($attempt->status === 'failed')
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-rose-100 text-2xl text-rose-700">!</span>
            <h1 class="mt-6 text-3xl font-black text-slate-900">Tu cuestionario sigue guardado</h1><p class="mt-1 text-sm font-semibold text-rose-600">No se completó la generación</p>
            <p class="mx-auto mt-3 max-w-lg leading-7 text-slate-600">{{ $attempt->generation_error }}</p>
            <form method="POST" action="{{ route('adaptive.diagnostic.retry', $attempt) }}" class="mt-6">@csrf<button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white">Reintentar generar ruta</button></form>
        @endif
    </section>
</div>
@endsection
