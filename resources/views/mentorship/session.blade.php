@extends('layouts.platform')

@section('content')
<div class="mx-auto w-full max-w-5xl">
    <a href="{{ $isMentor ? route('mentor.dashboard') : route('adaptive.mentorship') }}" class="inline-flex text-sm font-bold text-blue-700 hover:text-blue-900">← Volver a mentorías</a>
    @php($statusLabel = match($session->status) { 'accepted', 'scheduled' => 'Mentoría aceptada', 'rejected' => 'Solicitud no disponible', 'cancelled' => 'Sesión cancelada', 'completed' => 'Sesión finalizada', default => 'Solicitud pendiente' })
    <section class="mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-950 to-blue-800 px-6 py-7 text-white sm:px-8"><div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><h1 class="text-2xl font-extrabold sm:text-3xl">{{ $session->topic }}</h1><p class="mt-2 text-sm text-blue-100">{{ $statusLabel }}</p></div><span class="w-fit rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold">{{ $session->created_at->format('d/m/Y') }}</span></div></div>
        <div class="grid gap-7 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_minmax(280px,.72fr)]">
            <div>
                @if($isMentor)
                    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-extrabold uppercase tracking-[0.14em] text-blue-700">Persona solicitante</p><div class="mt-4 flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-xl bg-blue-100 text-lg font-extrabold text-blue-800">{{ Str::upper(Str::substr($session->student->name, 0, 1)) }}</span><div><p class="font-extrabold text-slate-900">{{ $session->student->name }}</p><p class="text-sm text-slate-600">{{ $session->student->email }}</p></div></div><dl class="mt-5 space-y-3 border-t border-slate-200 pt-4 text-sm"><div><dt class="font-bold text-slate-500">Objetivo profesional</dt><dd class="mt-1 text-slate-800">{{ $session->student->studentProfile?->professional_goal ?: 'No registrado' }}</dd></div><div><dt class="font-bold text-slate-500">Nivel actual</dt><dd class="mt-1 text-slate-800">{{ $session->student->studentProfile?->current_level ?: 'Inicial' }}</dd></div></dl></section>
                    @if($session->status === 'requested')
                        <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5"><h2 class="font-extrabold text-amber-950">Decide con claridad y respeto</h2><p class="mt-2 text-sm leading-6 text-amber-900">Acepta si el tema se alinea con tus especialidades y elige una fecha. La duración se toma de tu configuración ({{ $session->mentor->mentorProfile?->session_duration_minutes ?: 30 }} minutos).</p><form method="POST" action="{{ route('mentor.sessions.accept', $session) }}" class="mt-5 rounded-xl border border-emerald-200 bg-white p-4">@csrf<label class="text-sm font-semibold text-slate-700">Fecha y hora de la sesión</label><input type="datetime-local" name="scheduled_at" required value="{{ $session->preferred_at?->format('Y-m-d\\TH:i') }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm"><button class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">Aceptar y programar</button></form><details class="mt-3 rounded-xl border border-rose-200 bg-white"><summary class="cursor-pointer px-4 py-3 text-sm font-bold text-rose-700">No puedo aceptar esta solicitud</summary><form method="POST" action="{{ route('mentor.sessions.reject', $session) }}" class="border-t border-rose-100 p-4">@csrf<label class="text-sm font-semibold text-slate-700">Comentario opcional</label><textarea name="reason" rows="3" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="Ej. Este tema está fuera de mi especialidad."></textarea><button class="mt-3 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-rose-700">Enviar respuesta</button></form></details></section>
                    @endif
                @else
                    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-extrabold uppercase tracking-[0.14em] text-blue-700">Tu mentor</p><div class="mt-4 flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-xl bg-blue-100 text-lg font-extrabold text-blue-800">{{ Str::upper(Str::substr($session->mentor->name, 0, 1)) }}</span><div><p class="font-extrabold text-slate-900">{{ $session->mentor->name }}</p><p class="text-sm text-slate-600">{{ $session->mentor->mentorProfile?->headline ?: 'Mentor profesional' }}</p></div></div></section>
                @endif

                @if(in_array($session->status, ['accepted', 'scheduled'], true))
                    <section x-data="mentorshipChat()" x-init="startPolling()" class="mt-6"><h2 class="text-xl font-extrabold text-slate-900">Mensajes de la mentoría</h2><div id="mentorship-messages" class="mt-4 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">@forelse($session->messages as $message)<div data-message-id="{{ $message->id }}" class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}"><article class="max-w-[88%] rounded-2xl px-4 py-3 text-sm leading-6 {{ $message->sender_id === auth()->id() ? 'rounded-br-sm bg-blue-700 text-white' : 'rounded-bl-sm bg-white text-slate-700 shadow-sm' }}"><p>{{ $message->body }}</p><p class="mt-2 text-xs {{ $message->sender_id === auth()->id() ? 'text-blue-50' : 'text-slate-500' }}">{{ $message->is_automatic ? 'Mensaje automático · ' : '' }}{{ $message->created_at->format('d/m H:i') }}</p></article></div>@empty<p class="py-6 text-center text-sm text-slate-500">La conversación comenzará con el mensaje de bienvenida.</p>@endforelse</div><form method="POST" action="{{ route('mentorship.sessions.messages.send', $session) }}" @submit.prevent="send" class="mt-4">@csrf<textarea x-model="body" name="body" required maxlength="2000" rows="3" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Escribe un mensaje claro y respetuoso..."></textarea><div class="mt-3 flex items-center justify-between gap-3"><p x-show="error" x-text="error" class="text-sm text-rose-600"></p><button :disabled="sending" class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60" x-text="sending ? 'Enviando…' : 'Enviar mensaje'"></button></div></form></section>
                @elseif($session->status === 'completed')
                    <section class="mt-6"><div class="flex flex-wrap items-baseline justify-between gap-2"><h2 class="text-xl font-extrabold text-slate-900">Conversación de la mentoría</h2><p class="text-sm text-slate-500">Finalizada{{ $session->completed_at ? ' el '.$session->completed_at->format('d/m/Y H:i') : '' }} · {{ $session->completedBy?->name ?? 'cierre automático' }}</p></div><div class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">La sesión terminó y la conversación quedó cerrada. Ambas personas recibieron una copia completa por correo.</div><div class="mt-4 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">@forelse($session->messages as $message)<div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}"><article class="max-w-[88%] rounded-2xl px-4 py-3 text-sm leading-6 {{ $message->sender_id === auth()->id() ? 'rounded-br-sm bg-blue-700 text-white' : 'rounded-bl-sm bg-white text-slate-700 shadow-sm' }}"><p class="whitespace-pre-line">{{ $message->body }}</p><p class="mt-2 text-xs {{ $message->sender_id === auth()->id() ? 'text-blue-50' : 'text-slate-500' }}">{{ $message->is_automatic ? 'Mensaje automático · ' : '' }}{{ $message->created_at->format('d/m H:i') }}</p></article></div>@empty<p class="py-6 text-center text-sm text-slate-500">La sesión terminó sin mensajes.</p>@endforelse</div></section>
                @elseif(! $isMentor && $session->decision_message)
                    <section class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-5"><h2 class="font-extrabold text-rose-900">Respuesta del mentor</h2><p class="mt-3 whitespace-pre-line text-sm leading-6 text-rose-800">{{ $session->decision_message }}</p></section>
                @endif
            </div>
            <aside class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Detalles</p><dl class="mt-4 space-y-4 text-sm"><div><dt class="font-bold text-slate-500">Tema</dt><dd class="mt-1 font-semibold text-slate-800">{{ $session->topic }}</dd></div><div><dt class="font-bold text-slate-500">Estado</dt><dd class="mt-1 text-slate-800">{{ $statusLabel }}</dd></div>@if($session->scheduled_at)<div><dt class="font-bold text-slate-500">Sesión programada</dt><dd class="mt-1 text-slate-800">{{ $session->scheduled_at->format('d/m/Y H:i') }} · {{ $session->duration_minutes }} min</dd></div>@endif @if($session->cancelled_at)<div><dt class="font-bold text-rose-600">Cancelada</dt><dd class="mt-1 whitespace-pre-line text-rose-700">{{ $session->cancellation_message }}</dd></div>@endif @if($session->responded_at)<div><dt class="font-bold text-slate-500">Respondida</dt><dd class="mt-1 text-slate-800">{{ $session->responded_at->format('d/m/Y H:i') }}</dd></div>@endif @if($session->completed_at)<div><dt class="font-bold text-slate-500">Finalizada</dt><dd class="mt-1 text-slate-800">{{ $session->completed_at->format('d/m/Y H:i') }}</dd></div>@endif</dl>@if($session->isActive())<form method="POST" action="{{ route('mentorship.sessions.finish', $session) }}" class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4" onsubmit="return confirm('¿Finalizar la sesión? La conversación se cerrará y ambas personas recibirán una copia completa por correo.')">@csrf<p class="text-sm font-bold text-slate-900">¿Terminaron?</p><p class="mt-1 text-xs leading-5 text-slate-600">Al finalizar, el chat se cierra y ambas personas reciben la conversación completa por correo.@if($session->scheduled_at) Si nadie la finaliza, se cierra sola {{ \App\Services\MentorshipCompletionService::AUTO_CLOSE_AFTER_HOURS }} horas después de que termine el horario programado.@endif</p><button class="mt-3 w-full rounded-lg bg-blue-700 px-3 py-2 text-sm font-bold text-white hover:bg-blue-800">Finalizar sesión</button></form>@endif @if($session->isActive())<details class="mt-6 rounded-xl border border-rose-200"><summary class="cursor-pointer px-4 py-3 text-sm font-bold text-rose-700">Cancelar sesión</summary><form method="POST" action="{{ route('mentor.sessions.cancel', $session) }}" class="border-t border-rose-100 p-4">@csrf<textarea name="message" rows="3" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Mensaje para la otra persona (opcional)"></textarea><button class="mt-3 rounded-lg bg-rose-600 px-3 py-2 text-sm font-bold text-white">Confirmar cancelación</button></form></details>@endif</aside>
        </div>
    </section>
</div>
<script>
function mentorshipChat() {
    return {
        body: '', sending: false, error: '', polling: null, lastMessageId: 0,
        startPolling() {
            const ids = [...document.querySelectorAll('[data-message-id]')].map((element) => Number(element.dataset.messageId));
            this.lastMessageId = ids.length ? Math.max(...ids) : 0;
            this.polling = window.setInterval(() => this.fetchNewMessages(), 2500);
        },
        async fetchNewMessages() {
            try {
                const response = await fetch('{{ route('mentorship.sessions.messages', $session) }}?after_id=' + this.lastMessageId, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-Background-Request': '1'}});
                if (!response.ok) return;
                const data = await response.json();
                data.messages.forEach((message) => this.appendMessage(message));
            } catch (error) {
                // La conversación continuará funcionando aunque una consulta de actualización falle.
            }
        },
        appendMessage(message) {
            if (message.id <= this.lastMessageId || document.querySelector(`[data-message-id="${message.id}"]`)) return;
            const row = document.createElement('div');
            row.dataset.messageId = message.id;
            row.className = `ag-animate-in flex ${message.mine ? 'justify-end' : 'justify-start'}`;
            row.innerHTML = `<article class="max-w-[88%] rounded-2xl px-4 py-3 text-sm leading-6"><p></p><p class="mt-2 text-xs"></p></article>`;
            const article = row.querySelector('article');
            article.className += message.mine ? ' rounded-br-sm bg-blue-700 text-white' : ' rounded-bl-sm bg-white text-slate-700 shadow-sm';
            row.querySelector('p').textContent = message.body;
            row.querySelectorAll('p')[1].textContent = (message.is_automatic ? 'Mensaje automático · ' : '') + message.created_at;
            document.querySelector('#mentorship-messages')?.appendChild(row);
            if (!message.mine) window.agToast?.('Nuevo mensaje en tu mentoría', {type: 'info'});
            this.lastMessageId = Math.max(this.lastMessageId, message.id);
            const box = document.querySelector('#mentorship-messages');
            if (box) box.scrollTop = box.scrollHeight;
        },
        async send(event) {
            const form = event.currentTarget;
            const text = this.body.trim();
            if (!text || this.sending) return;
            this.sending = true;
            this.error = '';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest'},
                    body: JSON.stringify({body: text}),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'No se pudo enviar el mensaje.');
                const row = document.createElement('div');
                row.dataset.messageId = data.id;
                row.className = 'ag-animate-in flex justify-end';
                row.innerHTML = `<article class="max-w-[88%] rounded-2xl rounded-br-sm bg-blue-700 px-4 py-3 text-sm leading-6 text-white"><p></p><p class="mt-2 text-xs text-blue-50"></p></article>`;
                row.querySelector('p').textContent = data.message;
                row.querySelectorAll('p')[1].textContent = data.created_at;
                document.querySelector('#mentorship-messages').appendChild(row);
                this.lastMessageId = Math.max(this.lastMessageId, data.id);
                this.body = '';
                document.querySelector('#mentorship-messages').scrollTop = document.querySelector('#mentorship-messages').scrollHeight;
            } catch (error) {
                this.error = error.message || 'No se pudo enviar el mensaje.';
            } finally {
                this.sending = false;
            }
        },
    };
}
</script>
@endsection
