<section class="learning-panel">
    <h2 class="text-balance text-xl font-extrabold text-slate-900">Mentoría</h2>
    @php($mentorName = $session?->mentor?->name)
    @if ($session && in_array($session->status, ['accepted', 'scheduled']) && $session->scheduled_at)
        <p class="mt-3 font-bold text-slate-900">{{ $session->topic }}</p>
        <p class="mt-1 text-sm leading-6 text-slate-600">{{ ucfirst($session->scheduled_at->locale('es')->translatedFormat('l j \d\e F, g:i a')) }} (hora de Guatemala) con {{ $mentorName ?? 'tu mentor' }}</p>
        <a href="{{ route('adaptive.mentorship') }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Ver mis mentorías</a>
    @elseif ($session && in_array($session->status, ['accepted', 'scheduled']))
        <p class="mt-3 font-bold text-slate-900">{{ $session->topic }}</p>
        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $mentorName ?? 'Tu mentor' }} aceptó tu solicitud. La fecha está por confirmar.</p>
        <a href="{{ route('adaptive.mentorship') }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Ver mis mentorías</a>
    @elseif ($session)
        <p class="mt-3 font-bold text-slate-900">{{ $session->topic }}</p>
        <p class="mt-1 text-sm leading-6 text-slate-600">Solicitud enviada a {{ $mentorName ?? 'tu mentor' }}. Te avisaremos cuando confirme la fecha.</p>
        <a href="{{ route('adaptive.mentorship') }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Ver mis mentorías</a>
    @else
        <p class="mt-2 text-sm leading-6 text-slate-600">Habla con una persona que trabaja en el área que te interesa.</p>
        <a href="{{ route('adaptive.mentorship') }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 hover:text-blue-900">Pedir una mentoría</a>
    @endif
</section>
