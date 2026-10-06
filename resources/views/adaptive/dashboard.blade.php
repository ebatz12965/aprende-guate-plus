@extends('layouts.platform')
@section('content')
@php($goal = $path?->goal ?: $pendingAttempt?->primary_answer)
@php($primary = 'inline-flex rounded-xl bg-blue-500 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-blue-400')
@php($bandLink = 'inline-flex min-h-11 items-center text-sm font-bold text-blue-200 underline-offset-4 hover:text-white hover:underline')
{{-- Mientras algo se genera, el panel consulta su estado y solo recarga cuando cambia. --}}
<div class="space-y-6 pb-20"
    @if ($polling) x-data="{ key: @js($pollKey) }"
        x-init="setInterval(async () => { try { const response = await fetch(@js(route('adaptive.dashboard.status')), { headers: { Accept: 'application/json' } }); if ((await response.json()).key !== key) window.location.reload(); } catch (error) {} }, 5000)" @endif>
    @if ($path && $pendingAttempt)
        <div class="flex flex-col gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900 dark:border-blue-900/60 dark:text-blue-50 sm:flex-row sm:items-center sm:justify-between">
            @if ($pendingAttempt->status === 'generating')
                <p role="status">Estamos generando tu nueva ruta. Cuando esté lista, reemplazará la actual.</p>
            @elseif ($pendingAttempt->status === 'failed')
                <p>No pudimos generar tu nueva ruta. Tu ruta actual sigue igual.</p>
                <form method="POST" action="{{ route('adaptive.diagnostic.retry', $pendingAttempt) }}">@csrf<button
                        class="rounded-xl bg-blue-600 px-4 py-3 font-bold text-white transition hover:bg-blue-700">Intentar de nuevo</button></form>
            @else
                <p>Tienes un diagnóstico sin terminar. Si lo terminas, tu nueva ruta reemplazará la actual.</p>
                <a href="{{ route('adaptive.diagnostic.questions', $pendingAttempt) }}"
                    class="inline-flex min-h-11 items-center font-bold text-blue-700 underline-offset-4 hover:underline">Continuar diagnóstico</a>
            @endif
        </div>
    @endif

    <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl dark:bg-slate-900 dark:shadow-none dark:ring-1 dark:ring-inset dark:ring-slate-700/60 sm:px-10 sm:py-10"
        style="background-image: radial-gradient(circle at 88% 0%, rgb(66 165 245 / .2), transparent 42%), radial-gradient(circle at 35% 120%, rgb(33 150 243 / .16), transparent 40%)">
        <div class="relative max-w-3xl">
            @switch($stage)
                @case('start')
                    <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Hola, {{ $firstName }}</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-300">Cuéntanos qué trabajo o habilidad buscas y armaremos una ruta por fases para llegar ahí.</p>
                    <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
                        <a href="{{ route('adaptive.diagnostic') }}" class="{{ $primary }}">Empezar diagnóstico</a>
                        <p class="text-sm text-slate-400">Una pregunta sobre tu meta y cuatro de opción múltiple.</p>
                    </div>
                @break

                @case('questions')
                    @if ($pendingAttempt->status === 'preparing')
                        <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Estamos preparando tus preguntas</h1>
                        <p class="mt-3 max-w-2xl leading-7 text-slate-300">Las adaptamos a tu meta: «{{ Str::limit($goal, 140) }}».</p>
                        <p class="mt-6 flex items-center gap-2 text-sm text-blue-200" role="status"><span class="ag-soft-pulse h-2 w-2 rounded-full bg-blue-300"></span>Te las mostraremos aquí en cuanto estén listas.</p>
                    @else
                        <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Tus preguntas están listas</h1>
                        <p class="mt-3 max-w-2xl leading-7 text-slate-300">Responde cuatro preguntas de opción múltiple y generaremos tu ruta para «{{ Str::limit($goal, 140) }}».</p>
                        <a href="{{ route('adaptive.diagnostic.questions', $pendingAttempt) }}" class="mt-6 {{ $primary }}">Responder diagnóstico</a>
                    @endif
                @break

                @case('generating')
                    <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Estamos armando tu ruta</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-300">Para tu meta: «{{ Str::limit($goal, 140) }}». Puedes cerrar esta página; la ruta te esperará aquí.</p>
                    <p class="mt-6 flex items-center gap-2 text-sm text-blue-200" role="status"><span class="ag-soft-pulse h-2 w-2 rounded-full bg-blue-300"></span>Te la mostraremos aquí en cuanto esté lista.</p>
                @break

                @case('failed')
                    <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">No pudimos generar tu ruta</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-300">Tus respuestas siguen guardadas. Intenta de nuevo; si vuelve a fallar, espera unos minutos o pide orientación a una persona.</p>
                    <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
                        <form method="POST" action="{{ route('adaptive.diagnostic.retry', $pendingAttempt) }}">@csrf<button class="{{ $primary }}">Generar mi ruta de nuevo</button></form>
                        <a href="{{ route('adaptive.mentorship') }}" class="{{ $bandLink }}">Pedir ayuda a un mentor</a>
                    </div>
                @break

                @case('empty')
                    <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Tu ruta quedó sin fases</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-300">Algo falló al armar «{{ Str::limit($path->title, 90) }}». Rehaz tu diagnóstico y generaremos una ruta nueva.</p>
                    <a href="{{ route('adaptive.diagnostic') }}" class="mt-6 {{ $primary }}">Rehacer mi diagnóstico</a>
                @break

                @case('active')
                    <h1 class="text-balance text-2xl font-extrabold leading-tight sm:text-3xl">
                        {{ $nextItem ? 'Sigue con la fase '.$nextItem->position.': '.Str::limit($nextItem->title, 90) : 'Tu ruta: '.Str::limit($path->title, 90) }}</h1>
                    <p class="mt-3 line-clamp-2 max-w-2xl leading-7 text-slate-300">Tu meta: {{ $path->goal ?: $path->title }}</p>
                @break

                @case('completed')
                    <div class="flex items-center gap-4">
                        <svg class="h-11 w-11 shrink-0 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>
                        <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">¡Completaste tu ruta!</h1>
                    </div>
                    <p class="mt-4 max-w-2xl leading-7 text-slate-300">Terminaste «{{ Str::limit($path->title, 90) }}»: {{ $total === 1 ? '1 fase completada' : $total.' fases completadas' }}{{ $submittedCount ? ' y '.($submittedCount === 1 ? '1 reto entregado' : $submittedCount.' retos entregados') : '' }}. Tu ruta ya aparece como logro en tu perfil profesional; complétalo para mostrárselo a empleadores.</p>
                @break
            @endswitch

            @if (in_array($stage, ['active', 'completed']))
                <div class="mt-6">
                    <div class="relative h-2 rounded-full bg-white/10" role="progressbar" aria-label="Avance de tu ruta" aria-valuemin="0"
                        aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-valuetext="{{ $completed }} de {{ $total }} fases completadas">
                        <div class="h-full rounded-full bg-blue-400" style="width: {{ $progress }}%"></div>
                        @foreach ([25, 50, 75] as $tick)
                            <span class="absolute top-1/2 h-3 w-0.5 -translate-y-1/2 rounded-full bg-white/40" style="left: {{ $tick }}%"></span>
                        @endforeach
                    </div>
                    <ol class="mt-2 grid grid-cols-4 text-xs font-semibold" aria-label="Retos de tu ruta">
                        @foreach ($milestones as $milestone)
                            <li class="flex items-center justify-end gap-1 {{ $progress >= $milestone ? 'text-blue-200' : 'text-slate-400' }}">
                                @if ($progress >= $milestone)
                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                                @endif
                                <span>Reto {{ $milestone }} %<span class="sr-only">{{ $progress >= $milestone ? ', desbloqueado' : ', bloqueado' }}</span></span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-3 text-sm tabular-nums text-slate-300">{{ $completed }} de {{ $total }} fases completadas · {{ $progress }} %</p>
                </div>
                <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
                    @if ($stage === 'active')
                        <a href="{{ $nextItem ? route('adaptive.path.item.show', $nextItem) : route('adaptive.path') }}" class="{{ $primary }}">{{ $nextItem ? 'Continuar fase '.$nextItem->position : 'Ver mi ruta' }}</a>
                        <a href="{{ route('adaptive.path') }}" class="{{ $bandLink }}">Ver todas las fases</a>
                    @else
                        <a href="{{ route('adaptive.career') }}" class="{{ $primary }}">Completar mi perfil profesional</a>
                        @unless ($pendingAttempt)
                            <a href="{{ route('adaptive.diagnostic') }}" class="{{ $bandLink }}">Empezar una nueva ruta</a>
                        @endunless
                    @endif
                </div>
                @if ($stage === 'active')
                    <details class="group mt-5 max-w-2xl text-sm text-slate-300">
                        <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-2 font-bold text-blue-200 hover:text-white">
                            <svg class="h-4 w-4 transition-transform group-open:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>¿Cómo avanza mi ruta?</summary>
                        <ul class="mt-2 list-disc space-y-1.5 pl-5 leading-6">
                            <li>Cada fase se desbloquea al aprobar el mini-quiz de la anterior.</li>
                            <li>Dentro de cada tema generas su lección y su práctica cuando la abres.</li>
                            <li>Al llegar al 25, 50, 75 y 100 % se desbloquea un reto opcional que se evalúa con una rúbrica.</li>
                        </ul>
                    </details>
                @endif
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        @if ($challenge)
            <section class="learning-panel lg:col-span-2">
                @switch($challenge['state'])
                    @case('ready')
                        <h2 class="text-balance text-xl font-extrabold text-slate-900">{{ $challenge['challenge']->title }}</h2>
                        <p class="mt-1 text-sm font-semibold text-blue-700">Reto del {{ $challenge['challenge']->milestone }} % · opcional</p>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ Str::limit($challenge['challenge']->description, 220) }}</p>
                        <a href="{{ route('adaptive.challenges.show', $challenge['challenge']) }}"
                            class="mt-5 inline-flex rounded-xl border border-blue-200 px-4 py-3 text-sm font-bold text-blue-700 transition hover:border-blue-400 hover:text-blue-900">Abrir reto</a>
                    @break

                    @case('preparing')
                        <h2 class="text-balance text-xl font-extrabold text-slate-900">Preparando tu reto del {{ $challenge['milestone'] }} %</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600" role="status">Lo armamos con los temas que ya completaste. Aparecerá aquí en cuanto esté listo.</p>
                    @break

                    @case('failed')
                        <h2 class="text-balance text-xl font-extrabold text-slate-900">No pudimos preparar tu reto del {{ $challenge['milestone'] }} %</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Tu avance no se pierde. Un administrador puede volver a prepararlo; mientras tanto, sigue con tu ruta.</p>
                        <a href="{{ route('adaptive.challenges') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Ver mis retos</a>
                    @break

                    @case('locked')
                        <h2 class="text-balance text-xl font-extrabold text-slate-900">Tu próximo reto se desbloquea al {{ $challenge['milestone'] }} %</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Te {{ $challenge['phasesLeft'] === 1 ? 'falta 1 fase' : 'faltan '.$challenge['phasesLeft'].' fases' }}. Cada reto se arma con lo que ya aprendiste y se evalúa con una rúbrica.</p>
                        <a href="{{ route('adaptive.challenges') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Ver todos los retos</a>
                    @break

                    @default
                        <h2 class="text-balance text-xl font-extrabold text-slate-900">Entregaste todos tus retos</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Revisa la evaluación de cada uno y agrégalos como evidencia a tu perfil.</p>
                        <a href="{{ route('adaptive.challenges') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Ver evaluaciones</a>
                @endswitch
            </section>
        @endif
        @if (app(\App\Support\Modules::class)->access(auth()->user(), 'mentoria') === 'allowed')
            @include('adaptive.partials.dashboard-mentorship')
        @endif
        @if (app(\App\Support\Modules::class)->access(auth()->user(), 'tutor') === 'allowed')
            @include('adaptive.partials.dashboard-tutor')
        @endif
    </div>

    @if ($stage === 'active' && ! $pendingAttempt)
        <p class="text-sm leading-6 text-slate-600">¿Cambió tu meta? <a href="{{ route('adaptive.diagnostic') }}"
                class="font-bold text-blue-700 underline underline-offset-4 hover:text-blue-900">Rehacer mi diagnóstico</a> crea una ruta nueva que reemplaza la actual.</p>
    @endif
</div>
@endsection
