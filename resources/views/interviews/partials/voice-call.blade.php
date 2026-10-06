{{--
    Llamada de voz con OpenAI Realtime (WebRTC). El servidor entrega una clave temporal; el audio va directo del
    navegador a OpenAI. Al colgar, se envía la transcripción para evaluarla con la misma rúbrica del chat.
--}}
@php $interviewerName = $stage->interviewer['name'] ?? 'La entrevistadora'; @endphp
<section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    x-data="voiceCall({ session: @js(route('adaptive.interviews.stage.voice.session', [$simulation, $stage])), finish: @js(route('adaptive.interviews.stage.voice.finish', [$simulation, $stage])), name: @js($interviewerName) })">
    <div class="flex flex-col items-center px-5 pb-6 pt-8 text-center">
        <span class="relative grid h-24 w-24 place-items-center rounded-full bg-blue-100 text-2xl font-black text-blue-800" aria-hidden="true">
            <span x-show="speaking" x-cloak class="absolute inset-0 animate-ping rounded-full bg-blue-300/60"></span>
            <span class="relative">{{ $initials }}</span>
        </span>
        <p class="mt-4 text-lg font-extrabold text-slate-900">{{ $interviewerName }}</p>
        <p class="mt-1 min-h-6 text-sm font-semibold text-slate-600" aria-live="polite" x-text="statusText()"></p>
        <p x-show="state === 'live'" x-cloak class="mt-2 font-mono text-2xl font-bold tabular-nums" :class="left() <= 30 ? 'text-rose-700' : 'text-slate-800'" x-text="clock()" aria-label="Tiempo restante"></p>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <button type="button" x-show="state === 'idle' || state === 'error'" @click="start()" class="inline-flex min-h-12 items-center rounded-xl bg-blue-700 px-6 text-sm font-bold text-white hover:bg-blue-800"
                x-text="state === 'error' ? 'Intentar la llamada otra vez' : 'Empezar la llamada'"></button>
            <button type="button" x-show="state === 'live'" x-cloak @click="toggleMute()" class="inline-flex min-h-12 items-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50"
                :aria-pressed="muted.toString()" x-text="muted ? 'Activar micrófono' : 'Silenciar micrófono'"></button>
            <button type="button" x-show="state === 'live'" x-cloak @click="hangUp()" class="inline-flex min-h-12 items-center rounded-xl bg-rose-700 px-6 text-sm font-bold text-white hover:bg-rose-800">Colgar y terminar</button>
        </div>
        <p x-show="error" x-cloak class="mt-4 max-w-lg text-sm font-medium text-rose-700" role="alert" x-text="error"></p>
        <p x-show="state === 'idle'" class="mt-4 max-w-lg text-sm leading-6 text-slate-600">
            Te pediremos permiso para usar el micrófono. Usa audífonos si puedes, para que no se escuche el eco. Habla con naturalidad; puedes interrumpir.
            La llamada dura como máximo <span x-text="Math.round(max / 60)"></span> minutos.
        </p>
    </div>

    <div x-show="entries.some((e) => e.text)" x-cloak class="border-t border-slate-100">
        <h2 class="px-5 pt-4 text-sm font-extrabold text-slate-900">Transcripción en vivo</h2>
        <div x-ref="log" class="max-h-72 space-y-3 overflow-y-auto p-5 text-sm leading-6" role="log" aria-label="Transcripción de la llamada">
            <template x-for="entry in entries.filter((e) => e.text)" :key="entry.id">
                <p :class="entry.role === 'candidate' ? 'text-right text-slate-800' : 'text-slate-600'">
                    <span class="block text-xs font-bold text-slate-500" x-text="entry.role === 'candidate' ? 'Tú' : name"></span>
                    <span x-text="entry.text"></span>
                </p>
            </template>
        </div>
    </div>

    <form x-show="state === 'idle' || state === 'error'" method="POST" action="{{ route('adaptive.interviews.stage.to-chat', [$simulation, $stage]) }}" class="border-t border-slate-100 p-4 text-center">
        @csrf
        <button class="text-sm font-bold text-blue-700 underline-offset-4 hover:underline">Prefiero hacer la entrevista por chat</button>
    </form>
    <audio x-ref="audio" autoplay class="hidden"></audio>
</section>

<script>
function voiceCall(config) {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
    const CLOSING = 'hemos terminado la entrevista';
    return {
        ...config, state: 'idle', error: '', max: {{ (int) config('ai.voice.max_seconds') }}, elapsed: 0, muted: false, speaking: false, listening: false,
        entries: [], usage: { input_tokens: 0, output_tokens: 0 }, pc: null, dc: null, mic: null, timer: null, warned: false, closingSeen: false,
        init() {
            window.addEventListener('beforeunload', (event) => { if (this.state === 'live') event.preventDefault(); });
        },
        statusText() {
            return {
                idle: 'Todo listo para llamar', connecting: 'Conectando…', saving: 'Guardando la entrevista…', error: 'La llamada no está activa',
                live: this.speaking ? `${this.name} está hablando…` : (this.listening ? 'Te escucha…' : (this.muted ? 'Micrófono silenciado' : 'En llamada')),
            }[this.state];
        },
        left() { return Math.max(0, this.max - this.elapsed); },
        clock() { const s = this.left(); return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`; },
        async start() {
            if (this.state === 'connecting') return;
            Object.assign(this, { error: '', state: 'connecting', entries: [], elapsed: 0, warned: false, closingSeen: false, usage: { input_tokens: 0, output_tokens: 0 } });
            try {
                this.mic = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true } });
            } catch (e) {
                return this.fail('No pudimos usar tu micrófono. Revisa que el navegador tenga permiso y que no lo esté usando otra aplicación.');
            }
            try {
                const response = await fetch(this.session, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) return this.fail(data.errors?.voice?.[0] || data.message || 'No se pudo preparar la llamada.');
                this.max = data.max_seconds;

                this.pc = new RTCPeerConnection();
                this.pc.ontrack = (event) => { this.$refs.audio.srcObject = event.streams[0]; };
                this.pc.onconnectionstatechange = () => {
                    if (['failed', 'disconnected'].includes(this.pc?.connectionState) && this.state === 'live') this.hangUp('Se cortó la conexión. Guardamos lo que alcanzaste a responder.');
                };
                this.mic.getTracks().forEach((track) => this.pc.addTrack(track, this.mic));
                this.dc = this.pc.createDataChannel('oai-events');
                this.dc.onopen = () => this.connected();
                this.dc.onmessage = (event) => { try { this.handle(JSON.parse(event.data)); } catch (e) {} };

                const offer = await this.pc.createOffer();
                await this.pc.setLocalDescription(offer);
                const sdp = await fetch('https://api.openai.com/v1/realtime/calls', {
                    method: 'POST', body: offer.sdp, headers: { Authorization: `Bearer ${data.client_secret}`, 'Content-Type': 'application/sdp' },
                });
                if (!sdp.ok) return this.fail('El servicio de voz no respondió. Inténtalo de nuevo en un momento o sigue por chat.');
                await this.pc.setRemoteDescription({ type: 'answer', sdp: await sdp.text() });
            } catch (e) {
                this.fail('No se pudo conectar la llamada. Revisa tu conexión a internet.');
            }
        },
        connected() {
            this.state = 'live';
            // La entrevistadora habla primero: saludo y primera pregunta.
            this.send({ type: 'response.create' });
            this.timer = window.setInterval(() => this.tick(), 1000);
        },
        tick() {
            this.elapsed++;
            if (!this.warned && this.left() <= 30) {
                this.warned = true;
                this.send({ type: 'conversation.item.create', item: { type: 'message', role: 'system', content: [{ type: 'input_text', text: 'Quedan 30 segundos. En tu próxima intervención agradece y cierra con la frase indicada.' }] } });
            }
            if (this.left() <= 0) this.hangUp();
        },
        send(event) { if (this.dc?.readyState === 'open') this.dc.send(JSON.stringify(event)); },
        entry(id, role) {
            let found = this.entries.find((e) => e.id === id);
            if (!found) { found = { id, role, text: '' }; this.entries.push(found); }
            if (role) found.role = role;
            return found;
        },
        handle(event) {
            switch (event.type) {
                case 'conversation.item.added':
                case 'conversation.item.created':
                    if (event.item?.type === 'message' && ['user', 'assistant'].includes(event.item.role)) this.entry(event.item.id, event.item.role === 'user' ? 'candidate' : 'interviewer');
                    break;
                case 'conversation.item.input_audio_transcription.completed':
                    this.entry(event.item_id, 'candidate').text = (event.transcript || '').trim();
                    this.scroll();
                    break;
                case 'response.output_audio_transcript.delta':
                    this.entry(event.item_id, 'interviewer').text += event.delta || '';
                    this.scroll();
                    break;
                case 'response.output_audio_transcript.done':
                    this.entry(event.item_id, 'interviewer').text = (event.transcript || '').trim();
                    if (event.transcript?.toLowerCase().includes(CLOSING)) this.closingSeen = true;
                    break;
                case 'output_audio_buffer.started': this.speaking = true; break;
                case 'output_audio_buffer.stopped':
                case 'output_audio_buffer.cleared':
                    this.speaking = false;
                    // Termina sola cuando la entrevistadora acaba de despedirse.
                    if (this.closingSeen) window.setTimeout(() => this.hangUp(), 800);
                    break;
                case 'input_audio_buffer.speech_started': this.listening = true; break;
                case 'input_audio_buffer.speech_stopped': this.listening = false; break;
                case 'response.done':
                    this.usage.input_tokens += event.response?.usage?.input_tokens || 0;
                    this.usage.output_tokens += event.response?.usage?.output_tokens || 0;
                    break;
            }
        },
        scroll() { this.$nextTick(() => { if (this.$refs.log) this.$refs.log.scrollTop = this.$refs.log.scrollHeight; }); },
        toggleMute() {
            this.muted = !this.muted;
            this.mic?.getAudioTracks().forEach((track) => { track.enabled = !this.muted; });
        },
        stop() {
            window.clearInterval(this.timer);
            this.mic?.getTracks().forEach((track) => track.stop());
            this.dc?.close(); this.pc?.close();
            this.pc = null; this.dc = null; this.mic = null; this.speaking = false; this.listening = false;
        },
        fail(message) { this.stop(); this.state = 'error'; this.error = message; },
        async hangUp(notice = '') {
            if (this.state !== 'live') return;
            this.state = 'saving';
            this.mic?.getAudioTracks().forEach((track) => { track.enabled = false; });
            // La transcripción de la última respuesta puede llegar un instante después.
            await new Promise((resolve) => window.setTimeout(resolve, 1500));
            const seconds = this.elapsed;
            this.stop();
            try {
                const response = await fetch(this.finish, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ transcript: this.entries.filter((e) => e.text).map((e) => ({ role: e.role, text: e.text })), usage: this.usage, seconds }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) return this.fail(data.errors?.voice?.[0] || data.message || 'No se pudo guardar la entrevista.');
                if (notice) this.error = notice;
                window.location.href = data.redirect;
            } catch (e) {
                this.fail('Se perdió la conexión al guardar la entrevista. Inténtalo de nuevo.');
            }
        },
    };
}
</script>
