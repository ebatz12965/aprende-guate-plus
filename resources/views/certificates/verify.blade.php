<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Certificado {{ $certificate->code }} | Aprende Guate</title>
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased selection:bg-blue-100 selection:text-blue-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-extrabold text-slate-900"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="h-9 w-9">Aprende Guate</a>
            <a href="{{ route('home') }}" class="text-sm font-bold text-blue-700 underline-offset-4 hover:underline">Conoce la plataforma</a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5 py-8 sm:py-12">
        <section class="flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 sm:flex-row sm:items-center sm:p-6" aria-labelledby="estado">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
            </span>
            <div>
                <h1 id="estado" class="text-2xl font-extrabold text-emerald-950">Certificado auténtico</h1>
                <p class="mt-1 leading-7 text-emerald-950">Aprende Guate emitió este certificado a <strong>{{ $certificate->recipient_name }}</strong> por completar la ruta de aprendizaje <strong>{{ $certificate->title }}</strong> el {{ $issuedOn }}.</p>
            </div>
        </section>

        <dl class="mt-6 grid gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4"><dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Certificado n.º</dt><dd class="mt-1 font-extrabold tabular-nums text-slate-900">{{ $certificate->code }}</dd></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Contenido</dt><dd class="mt-1 font-extrabold text-slate-900">{{ $certificate->phases }} fases · {{ $certificate->topics }} temas</dd></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Duración estimada</dt><dd class="mt-1 font-extrabold text-slate-900">{{ $certificate->hours ? $certificate->hours.' horas' : 'No indicada' }}</dd></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Firmado por</dt><dd class="mt-1 font-extrabold text-slate-900">{{ $certificate->signer_name }}</dd><dd class="text-sm text-slate-600">{{ $certificate->signer_title }}</dd></div>
        </dl>

        <div class="mt-6">@include('certificates.preview')</div>

        <p class="mt-6 max-w-3xl text-sm leading-6 text-slate-600">En Aprende Guate cada persona recibe una ruta de aprendizaje según su meta laboral y la completa con lecciones, prácticas evaluadas y un mini-quiz por fase. Esta página solo muestra los datos impresos en el certificado.</p>
    </main>
</body>
</html>
