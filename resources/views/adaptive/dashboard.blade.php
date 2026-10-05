@extends('layouts.platform')
@section('content')
@php($goal = $path?->goal ?: $pendingAttempt?->primary_answer)
<div class="space-y-6">
    @if ($path && $pendingAttempt)
        <div class="flex flex-col gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900 sm:flex-row sm:items-center sm:justify-between">
            @if ($pendingAttempt->status === 'generating')
                <p>Estamos generando tu nueva ruta. Cuando esté lista, reemplazará la actual.</p>
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

    <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl sm:px-10 sm:py-10"
        @if (in_array($stage, ['questions', 'generating']) && in_array($pendingAttempt?->status, ['preparing', 'generating'])) x-data x-init="setTimeout(() => window.location.reload(), 5000)" @endif>
        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-blue-400/15 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"></div>
        <div class="relative max-w-3xl">
            @switch($stage)
                @case('start')
                    <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Hola, {{ $firstName }}</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-300">Cuéntanos qué trabajo o habilidad buscas y armaremos una ruta por fases para llegar ahí.</p>
                    <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
                        <a href="{{ route('adaptive.diagnostic') }}"
                            class="rounded-xl bg-blue-500 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-blue-400">Empezar diagnóstico</a>
                        <p class="text-sm text-slate-400">Una pregunta sobre tu meta y cuatro de opción múltiple.</p>
                    </div>
                @break

                @case('questions')
                    @if ($pendingAttempt->status === 'preparing')
                        <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Estamos preparando tus preguntas</h1>
                        <p class="mt-3 max-w-2xl leading-7 text-slate-300">Las adaptamos a tu meta: «{{ Str::limit($goal, 140) }}».</p>
                        <p class="mt-6 flex items-center gap-2 text-sm text-blue-200" role="status"><span class="ag-soft-pulse h-2 w-2 rounded-full bg-blue-300"></span>Esta página se actualiza sola.</p>
                    @else
                        <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Tus preguntas están listas</h1>
                        <p class="mt-3 max-w-2xl leading-7 text-slate-300">Responde cuatro preguntas de opción múltiple y generaremos tu ruta para «{{ Str::limit($goal, 140) }}».</p>
                        <a href="{{ route('adaptive.diagnostic.questions', $pendingAttempt) }}"
                            class="mt-6 inline-flex rounded-xl bg-blue-500 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-blue-400">Responder diagnóstico</a>
                    @endif
                @break

                @case('generating')
                    <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">Estamos armando tu ruta</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-300">Para tu meta: «{{ Str::limit($goal, 140) }}». Puedes seguir navegando mientras tanto.</p>
                    <p class="mt-6 flex items-center gap-2 text-sm text-blue-200" role="status"><span class="ag-soft-pulse h-2 w-2 rounded-full bg-blue-300"></span>Esta página se actualiza sola.</p>
                @break

                @case('failed')
                    <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">No pudimos generar tu ruta</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-300">Tus respuestas siguen guardadas. Intenta de nuevo; si vuelve a fallar, espera unos minutos.</p>
                    <form method="POST" action="{{ route('adaptive.diagnostic.retry', $pendingAttempt) }}" class="mt-6">@csrf<button
                            class="rounded-xl bg-blue-500 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-blue-400">Generar mi ruta de nuevo</button></form>
                @break

                @case('active')
                    <h1 class="text-balance text-2xl font-extrabold leading-tight sm:text-3xl">Tu meta: {{ Str::limit($path->goal ?: $path->title, 160) }}</h1>
                    @if ($nextItem)
                        <p class="mt-3 leading-7 text-slate-300">Fase {{ $nextItem->position }} de {{ $total }}: <span class="font-bold text-white">{{ $nextItem->title }}</span></p>
                    @endif
                @break

                @case('completed')
                    <h1 class="text-balance text-3xl font-extrabold sm:text-4xl">¡Completaste tu ruta!</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-300">Terminaste «{{ $path->title }}». Ahora muestra lo que aprendiste en tu perfil profesional.</p>
                @break
            @endswitch

            @if (in_array($stage, ['active', 'completed']))
                <div class="mt-6">
                    <div class="relative h-2 rounded-full bg-white/10" role="progressbar" aria-label="Avance de tu ruta" aria-valuemin="0"
                        aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-valuetext="{{ $completed }} de {{ $total }} fases completadas">
                        <div class="h-full rounded-full bg-blue-400" style="width: {{ $progress }}%"></div>
                        @foreach ([25, 50, 75] as $tick)
                            <span class="absolute top-1/2 h-3 w-0.5 -translate-y-1/2 bg-slate-950" style="left: {{ $tick }}%"></span>
                        @endforeach
                    </div>
                    <ol class="mt-2 grid grid-cols-4 text-xs font-semibold">
                        @foreach ($milestones as $milestone)
                            <li class="text-right {{ $progress >= $milestone ? 'text-blue-200' : 'text-slate-400' }}">Reto {{ $milestone }} %</li>
                        @endforeach
                    </ol>
                    <p class="mt-3 text-sm tabular-nums text-slate-300">{{ $completed }} de {{ $total }} fases completadas · {{ $progress }} %</p>
                </div>
                <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
                    @if ($stage === 'active')
                        <a href="{{ $nextItem ? route('adaptive.path.item.show', $nextItem) : route('adaptive.path') }}"
                            class="rounded-xl bg-blue-500 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-blue-400">{{ $nextItem ? 'Continuar fase '.$nextItem->position : 'Ver mi ruta' }}</a>
                        <a href="{{ route('adaptive.path') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-blue-200 underline-offset-4 hover:underline">Ver todas las fases</a>
                    @else
                        <a href="{{ route('adaptive.career') }}"
                            class="rounded-xl bg-blue-500 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-blue-400">Completar mi perfil profesional</a>
                    @endif
                    @unless ($pendingAttempt)
                        <a href="{{ route('adaptive.diagnostic') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-300 underline-offset-4 hover:underline">{{ $stage === 'active' ? 'Rehacer mi diagnóstico' : 'Empezar una nueva ruta' }}</a>
                    @endunless
                </div>
                @if ($stage === 'active' && ! $pendingAttempt)
                    <p class="mt-2 text-xs text-slate-400">Rehacer el diagnóstico crea una ruta nueva que reemplaza la actual.</p>
                @endif
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        @if ($challenge)
            <section class="learning-panel lg:col-span-2">
                @switch($challenge['state'])
                    @case('ready')
                        <h2 class="text-balance text-xl font-extrabold text-slate-900">{{ $challenge['challenge']->title }}</h2>
                        <p class="mt-1 text-sm font-semibold text-blue-700">Reto del {{ $challenge['challenge']->milestone }} % · opcional</p>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ Str::limit($challenge['challenge']->description, 220) }}</p>
                        <a href="{{ route('adaptive.challenges.show', $challenge['challenge']) }}"
                            class="mt-5 inline-flex rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-700">Abrir reto</a>
                    @break

                    @case('preparing')
                        <h2 class="text-balance text-xl font-extrabold text-slate-900">Preparando tu reto del {{ $challenge['milestone'] }} %</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Lo armamos con los temas que ya completaste. Aparecerá aquí en unos minutos.</p>
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

        <div class="space-y-6 {{ $challenge ? '' : 'lg:col-span-3' }}">
            @if ($challenge)
                <section class="learning-panel">
                    <h2 class="text-balance text-xl font-extrabold text-slate-900">Mentoría</h2>
                    @if (in_array($session?->status, ['accepted', 'scheduled']))
                        <p class="mt-3 font-bold text-slate-900">{{ $session->topic }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ ucfirst($session->scheduled_at->translatedFormat('l j \d\e F, g:i a')) }} (hora de Guatemala) con {{ $session->mentor?->name }}</p>
                        <a href="{{ route('adaptive.mentorship') }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Ver mis mentorías</a>
                    @elseif ($session)
                        <p class="mt-3 font-bold text-slate-900">{{ $session->topic }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Solicitud enviada a {{ $session->mentor?->name }}. Te avisaremos cuando confirme la fecha.</p>
                        <a href="{{ route('adaptive.mentorship') }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Ver mis mentorías</a>
                    @else
                        <p class="mt-2 text-sm leading-6 text-slate-600">Habla con una persona que trabaja en el área que te interesa.</p>
                        <a href="{{ route('adaptive.mentorship') }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Pedir una mentoría</a>
                    @endif
                </section>
            @endif
            <section class="learning-panel bg-gradient-to-br from-blue-50 to-blue-50">
                <img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="h-10 w-10 rounded-xl object-cover shadow-sm">
                <h2 class="mt-5 text-xl font-extrabold text-slate-900">Destraba tu siguiente paso</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Explica qué intentaste y recibe una guía breve, ordenada y conectada con tu aprendizaje.</p>
                <a href="{{ route('adaptive.tutor') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Preguntar al Tutor IA</a>
            </section>
        </div>
    </div>
</div>
@endsection
