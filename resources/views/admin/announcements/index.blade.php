@extends('layouts.admin')

@section('content')
    <div class="max-w-6xl">
        <div>
            <p class="text-xs font-extrabold tracking-[0.16em] text-blue-700">COMUNICACIÓN</p>
            <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Avisos de la plataforma</h1>
            <p class="mt-2 max-w-2xl text-slate-600">Redacta información, avisos o recordatorios y decide si se entregan en la aplicación y por correo según las
                preferencias de cada persona.</p>
        </div>
        <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-extrabold text-slate-900">Nuevo aviso</h2>
                <form method="POST" action="{{ route('admin.announcements.store') }}" class="mt-5 space-y-5">@csrf<div><label
                            class="text-sm font-bold text-slate-700">Título</label><input required maxlength="160" name="title" value="{{ old('title') }}"
                            class="mt-2 w-full rounded-xl border-slate-200" placeholder="Ej. Mantenimiento programado"></div>
                    <div><label class="text-sm font-bold text-slate-700">Mensaje</label>
                        <textarea required maxlength="3000" name="body" rows="7" class="mt-2 w-full rounded-xl border-slate-200"
                            placeholder="Escribe información clara, fecha, horario y acción esperada.">{{ old('body') }}</textarea>
                    </div>
                    <div><label class="text-sm font-bold text-slate-700">Audiencia</label><select name="audience" class="mt-2 w-full rounded-xl border-slate-200">
                            <option value="all">Toda la plataforma</option>
                            <option value="students">Solo estudiantes</option>
                            <option value="mentors">Mentores y administradores</option>
                        </select></div><label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="send_email" value="1"
                            class="rounded border-slate-300 text-blue-600"> Enviar también por correo a quienes lo tengan habilitado</label><label
                        class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="publish_now" value="1"
                            class="rounded border-slate-300 text-blue-600"> Publicar y notificar ahora</label><button
                        class="w-full rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white hover:bg-blue-800">Guardar aviso</button>
                </form>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-extrabold text-slate-900">Historial</h2><span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $announcements->count() }}</span>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse($announcements as $announcement)
                        <article class="rounded-xl border border-slate-200 p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="font-extrabold text-slate-900">{{ $announcement->title }}</h3>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $announcement->body }}</p>
                                </div><span
                                    class="w-fit rounded-full px-2.5 py-1 text-xs font-bold {{ $announcement->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $announcement->is_published ? 'Publicado' : 'Borrador' }}</span>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                                <span>{{ $announcement->audience }} · {{ $announcement->author->name }} ·
                                    {{ $announcement->created_at->format('d/m/Y H:i') }}</span>
                                @if (!$announcement->is_published)
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">@csrf<button
                                                class="font-bold text-blue-700">Publicar</button></form>
                                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}">@csrf @method('DELETE')<button
                                                class="font-bold text-rose-700">Eliminar</button></form>
                                </div>@else<span>{{ $announcement->published_at?->format('d/m/Y H:i') }}</span>
                                @endif
                            </div>
                    </article>@empty<p class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">Aún no se han creado avisos.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
