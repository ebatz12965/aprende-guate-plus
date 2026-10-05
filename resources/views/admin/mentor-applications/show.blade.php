@extends('layouts.admin')
@section('content')
    <div class="max-w-5xl">
        <a href="{{ route('admin.mentor-applications.index') }}" class="text-sm font-bold text-blue-700">← Volver a solicitudes</a>
        <div class="mt-4 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-bold text-blue-700">POSTULACIÓN DE MENTOR</p>
                <h1 class="mt-2 text-3xl font-extrabold text-slate-900">{{ $application->user->name }}</h1>
                <p class="mt-1 text-slate-600">{{ $application->user->email }} · {{ $application->professional_title }}</p>
            </div><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">{{ ucfirst(str_replace('_', ' ', $application->status)) }}</span>
        </div>
        <div class="mt-7 grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-slate-900">Datos profesionales</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-bold text-slate-500">Especialidades</dt>
                        <dd class="mt-1 text-slate-800">{{ $application->specialties }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-500">Experiencia</dt>
                        <dd class="mt-1 text-slate-800">{{ $application->years_experience }} años</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-500">Trayectoria</dt>
                        <dd class="mt-1 whitespace-pre-line leading-6 text-slate-800">{{ $application->professional_background }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-500">Enfoque</dt>
                        <dd class="mt-1 whitespace-pre-line leading-6 text-slate-800">{{ $application->teaching_approach }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-500">Motivación</dt>
                        <dd class="mt-1 whitespace-pre-line leading-6 text-slate-800">{{ $application->motivation }}</dd>
                    </div>
                </dl>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-slate-900">Cuestionario privado</h2>
                <div class="mt-5 space-y-5">
                    @foreach ($application->questionnaire_answers as $answer)
                        <div>
                            <p class="text-sm font-bold text-blue-700">{{ $answer['question'] }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $answer['answer'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
        @if ($application->status === 'pending_review')
            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-slate-900">Decisión administrativa</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <form method="POST" action="{{ route('admin.mentor-applications.approve', $application) }}"
                        class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">@csrf<label class="form-label" for="approve-notes">Notas opcionales</label>
                        <textarea id="approve-notes" name="admin_notes" rows="3" class="form-input" placeholder="Observaciones para el expediente"></textarea><button class="mt-3 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-extrabold text-white">Aprobar como
                            mentor</button>
                    </form>
                    <form method="POST" action="{{ route('admin.mentor-applications.reject', $application) }}"
                        class="rounded-xl border border-rose-200 bg-rose-50 p-4">@csrf<label class="form-label" for="reject-notes">Motivo de rechazo</label>
                        <textarea id="reject-notes" name="admin_notes" rows="3" required class="form-input" placeholder="Explica qué debe mejorar o por qué no procede"></textarea><button class="mt-3 rounded-xl bg-rose-700 px-4 py-2.5 text-sm font-extrabold text-white">Rechazar
                            solicitud</button>
                    </form>
                </div>
            </section>
        @endif
    </div>
@endsection
