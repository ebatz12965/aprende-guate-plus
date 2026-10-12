@extends('layouts.platform')
@section('content')
<div class="mx-auto max-w-4xl">
    <h1 class="mt-2 text-3xl font-extrabold">Tu apoyo para aprender, 24/7</h1>
    <p class="mt-2 text-slate-500">Haz una pregunta concreta. El Tutor responde de forma breve para ayudarte a avanzar.</p>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="tutorChat()" x-init="formatSavedMessages()">
        <div id="tutor-messages" class="h-[420px] space-y-4 overflow-y-auto p-5">
            <div class="max-w-xl rounded-2xl rounded-tl-sm bg-blue-50 p-4 text-sm text-blue-950">Hola, soy tu Tutor IA. ¿Qué estás aprendiendo hoy?</div>
            @foreach($conversation->messages as $message)
                <div class="flex {{ $message->role === 'user' ? 'justify-end' : '' }}">
                    <div class="tutor-message max-w-xl rounded-2xl p-4 text-sm {{ $message->role === 'user' ? 'rounded-tr-sm bg-blue-600 text-white' : 'rounded-tl-sm bg-slate-100 text-slate-800' }}">{{ $message->content }}</div>
                </div>
            @endforeach
        </div>
        <form @submit.prevent="send" class="border-t border-slate-100 p-4">
            <div class="flex gap-3">
                <textarea x-model="message" required maxlength="600" rows="2" placeholder="Escribe tu pregunta..." class="flex-1 resize-none rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500"></textarea>
                <button :disabled="loading" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50" x-text="loading ? 'Pensando...' : 'Enviar'"></button>
            </div>
            <p class="mt-2 text-right text-xs text-slate-500"><span x-text="message.length"></span>/600 caracteres</p>
        </form>
    </section>
</div>
<script nonce="{{ Vite::cspNonce() }}">
function tutorChat() {
    return {
        message: '', loading: false,
        formatSavedMessages() {
            document.querySelectorAll('.tutor-message').forEach((bubble) => {
                this.formatBubble(bubble, bubble.textContent);
            });
            this.scrollToBottom();
        },
        addMessage(role, text) {
            const row = document.createElement('div');
            row.className = `flex ${role === 'user' ? 'justify-end' : ''}`;
            const bubble = document.createElement('div');
            bubble.className = `tutor-message max-w-xl rounded-2xl p-4 text-sm ${role === 'user' ? 'rounded-tr-sm bg-blue-600 text-white' : 'rounded-tl-sm bg-slate-100 text-slate-800'}`;
            this.formatBubble(bubble, text);
            row.appendChild(bubble);
            document.querySelector('#tutor-messages').appendChild(row);
        },
        formatBubble(bubble, text) {
            bubble.replaceChildren(this.formatContent(text));
        },
        formatContent(text) {
            const fragment = document.createDocumentFragment();
            let list = null;
            String(text)
                .replace(/\r\n?/g, '\n')
                .replace(/([:;.])\s+(?=\d+[.)]\s+\*\*)/g, '$1\n')
                .split('\n').forEach((rawLine) => {
                const line = rawLine.trim();
                const numbered = line.match(/^\d+[.)]\s+(.+)$/);
                const bullet = line.match(/^[-•]\s+(.+)$/);
                if (numbered || bullet) {
                    const type = numbered ? 'ol' : 'ul';
                    if (!list || list.tagName.toLowerCase() !== type) {
                        list = document.createElement(type);
                        list.className = `${type === 'ol' ? 'list-decimal' : 'list-disc'} ml-5 space-y-2`;
                        fragment.appendChild(list);
                    }
                    const item = document.createElement('li');
                    this.appendInlineText(item, (numbered || bullet)[1]);
                    list.appendChild(item);
                    return;
                }
                list = null;
                if (!line) return;
                const paragraph = document.createElement('p');
                paragraph.className = fragment.childNodes.length ? 'mb-3' : '';
                this.appendInlineText(paragraph, line);
                fragment.appendChild(paragraph);
            });
            return fragment;
        },
        appendInlineText(element, text) {
            String(text).split(/(\*\*[^*]+\*\*)/g).forEach((part) => {
                if (part.startsWith('**') && part.endsWith('**')) {
                    const strong = document.createElement('strong');
                    strong.textContent = part.slice(2, -2);
                    element.appendChild(strong);
                    return;
                }
                element.appendChild(document.createTextNode(part));
            });
        },
        async send() {
            if (!this.message.trim()) return;
            const text = this.message.trim();
            this.message = '';
            this.addMessage('user', text);
            this.loading = true;
            try {
                const response = await fetch('{{ route('adaptive.tutor.message') }}', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                    body: JSON.stringify({message: text}),
                });
                const data = await response.json();
                this.addMessage('assistant', data.reply || 'No pude responder.');
            } catch (error) {
                alert('No se pudo enviar el mensaje.');
            }
            this.loading = false;
            this.$nextTick(() => this.scrollToBottom());
        },
        scrollToBottom() {
            const box = document.querySelector('#tutor-messages');
            box.scrollTop = box.scrollHeight;
        },
    };
}
</script>
@endsection
