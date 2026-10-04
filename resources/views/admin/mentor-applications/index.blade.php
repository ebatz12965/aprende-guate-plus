@extends('layouts.admin')
@section('content')
    <div class="max-w-[1500px]">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-bold text-blue-700">GESTIÓN DE MENTORES</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Solicitudes para ser mentor</h1>
                <p class="mt-2 text-slate-500">Revisa los datos profesionales y el cuestionario antes de autorizar el rol.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['pending_review' => 'Pendientes', 'approved' => 'Aprobadas', 'rejected' => 'Rechazadas'] as $key => $label)
                    <a href="{{ route('admin.mentor-applications.index', ['status' => $key]) }}"
                        class="rounded-xl px-3 py-2 text-sm font-bold {{ $status === $key ? 'bg-blue-700 text-white' : 'border border-slate-200 bg-white text-slate-600' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($applications as $application)
                    <a href="{{ route('admin.mentor-applications.show', $application) }}" class="block px-5 py-5 transition hover:bg-blue-50 sm:px-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-extrabold text-slate-900">{{ $application->user->name }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $application->professional_title }} · {{ $application->specialties }}</p>
                                <p class="mt-1 text-xs text-slate-500">Enviada {{ $application->created_at->format('d/m/Y H:i') }}</p>
                            </div><span
                                class="rounded-full px-3 py-1 text-xs font-bold {{ $status === 'pending_review' ? 'bg-amber-50 text-amber-700' : ($status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700') }}">{{ $status === 'pending_review' ? 'Pendiente' : ucfirst($status === 'approved' ? 'Aprobada' : 'Rechazada') }}</span>
                        </div>
                </a>@empty<div class="px-6 py-12 text-center text-sm text-slate-500">No hay solicitudes en este estado.</div>
                @endforelse
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $applications->links() }}</div>
        </section>
    </div>
@endsection
