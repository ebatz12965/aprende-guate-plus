@extends('layouts.platform')

@section('content')
    <div class="mx-auto w-full max-w-6xl" x-data="mentorRequestModal()">
        <div class="max-w-2xl">
            <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Conecta con personas que te orientan</h1>
            <p class="mt-3 text-slate-600">Solicita apoyo en un tema concreto. Cuando un mentor acepte, podrás conversar de forma privada desde la plataforma.</p>
        </div>
        <section class="mt-7">
            <h2 class="text-xl font-extrabold text-slate-900">Mentores disponibles</h2>
            <p class="mt-2 text-sm text-slate-500">Selecciona un mentor para consultar y enviar tu solicitud.</p>
            <div class="mt-4 grid gap-5 lg:grid-cols-2">
                @forelse($mentors as $mentor)
                    <article role="button" tabindex="0"
                        @click="openMentor({{ $mentor->user_id }}, @js($mentor->user->name), @js($mentor->headline ?: 'Mentor profesional'), '{{ route('adaptive.mentorship.request', $mentor) }}')"
                        @keydown.enter="openMentor({{ $mentor->user_id }}, @js($mentor->user->name), @js($mentor->headline ?: 'Mentor profesional'), '{{ route('adaptive.mentorship.request', $mentor) }}')"
                        class="cursor-pointer rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="flex items-center gap-4"><span
                                class="grid h-12 w-12 place-items-center rounded-xl bg-blue-100 text-lg font-extrabold text-blue-800">{{ Str::upper(Str::substr($mentor->user->name, 0, 1)) }}</span>
                            <div>
                                <h3 class="font-extrabold text-slate-900">{{ $mentor->user->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $mentor->headline ?: 'Mentor profesional' }}</p>
                            </div>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-600">{{ $mentor->about ?: 'Disponible para acompañarte en tu desarrollo profesional.' }}</p>
                        @if ($mentor->specialties)
                            <p class="mt-3 text-sm font-bold text-blue-700">{{ $mentor->specialties }}</p>
                        @endif
                        <div class="mt-5 text-sm font-extrabold text-blue-700">
                            Seleccionar mentor <span aria-hidden="true">→</span></div>
                </article>@empty<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center lg:col-span-2">
                        <h3 class="font-extrabold text-slate-900">Estamos preparando la red de mentores</h3>
                        <p class="mt-2 text-sm text-slate-500">Pronto podrás solicitar orientación especializada.</p>
                    </div>
                @endforelse
            </div>
        </section>
        <section class="mt-9">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Mis solicitudes</h2>
                    <p class="mt-1 text-sm text-slate-500">Consulta las respuestas y entra a las mentorías aceptadas.</p>
                </div><span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-600">{{ $sessions->count() }}</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($sessions as $session)
                    @php(
    $statusClass = match ($session->status) {
        'accepted', 'scheduled' => 'bg-emerald-50 text-emerald-700',
        'rejected', 'cancelled' => 'bg-rose-50 text-rose-700',
        'completed' => 'bg-slate-100 text-slate-700',
        default => 'bg-amber-50 text-amber-700'
    }
)
                    <article class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-extrabold text-slate-900">{{ $session->topic }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $session->mentor->name }} · {{ $session->created_at->format('d/m/Y') }} @if ($session->scheduled_at)
                                    · {{ $session->scheduled_at->format('d/m/Y H:i') }}
                                @endif
                            </p>
                            @if (in_array($session->status, ['rejected', 'cancelled'], true) && ($session->decision_message || $session->cancellation_message))
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    {{ Str::limit($session->decision_message ?: $session->cancellation_message, 180) }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3"><span
                                class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ match ($session->status) {'requested' => 'Pendiente','accepted' => 'Aceptada','scheduled' => 'Programada','rejected' => 'No disponible','cancelled' => 'Cancelada','completed' => 'Finalizada',default => ucfirst($session->status)} }}</span>
                            @if (in_array($session->status, ['accepted', 'scheduled', 'completed'], true))
                                <a href="{{ route('mentorship.sessions.show', $session) }}"
                                    class="rounded-xl border border-blue-200 px-3 py-2 text-sm font-bold text-blue-700 hover:bg-blue-50">{{ $session->status === 'completed' ? 'Ver conversación' : 'Abrir conversación' }}</a>
                            @endif
                        </div>
                </article>@empty<p class="rounded-2xl bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">Aún no has solicitado una mentoría.</p>
                @endforelse
            </div>
        </section>
        <div x-cloak x-show="open" x-transition class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true"
            aria-labelledby="mentor-request-title" @keydown.escape.window="close()">
            <section @click.outside="close()" class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="mentor-request-title" class="text-2xl font-extrabold text-slate-900" x-text="mentorName"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="mentorHeadline"></p>
                    </div><button type="button" @click="close()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Cerrar">✕</button>
                </div>
                <form method="POST" :action="action" class="mt-6 space-y-5">@csrf
                    <div><label for="mentor-topic" class="text-sm font-bold text-slate-700">¿En qué tema necesitas orientación?</label>
                        <textarea id="mentor-topic" name="topic" required maxlength="160" rows="3" class="mt-2 w-full rounded-xl border-slate-200 text-sm"
                            placeholder="Describe brevemente el tema que quieres trabajar"></textarea>
                    </div>
                    <div><label for="mentor-preferred-at" class="text-sm font-bold text-slate-700">Horario que prefieres <span
                                class="font-normal text-slate-500">(opcional)</span></label><input id="mentor-preferred-at" type="datetime-local"
                            name="preferred_at" class="mt-2 w-full rounded-xl border-slate-200 text-sm">
                        <p class="mt-2 text-xs text-slate-500">El mentor confirmará la hora final según su disponibilidad.</p>
                    </div>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" @click="close()"
                            class="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancelar</button><button
                            class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-extrabold text-white hover:bg-blue-800">Solicitar mentoría</button></div>
                </form>
            </section>
        </div>
        <script>
            function mentorRequestModal() {
                return {
                    open: false,
                    mentorName: '',
                    mentorHeadline: '',
                    action: '',
                    openMentor(id, name, headline, action) {
                        this.mentorName = name;
                        this.mentorHeadline = headline;
                        this.action = action;
                        this.open = true;
                        this.$nextTick(() => document.querySelector('#mentor-topic')?.focus());
                    },
                    close() {
                        this.open = false;
                    },
                };
            }
        </script>
    </div>
@endsection
