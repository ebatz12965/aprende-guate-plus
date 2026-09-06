@extends('layouts.platform')

@section('content')
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-8">
        Mis Certificados
    </h2>

    @if($certificates->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($certificates as $certificate)
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                            </div>
                            <h3 class="text-xl font-semibold ml-4">{{ $certificate->course->title }}</h3>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Obtenido el: {{ $certificate->issued_at->format('d/m/Y') }}</p>
                        <a href="{{ route('certificate.show', $certificate) }}" target="_blank" class="w-full text-center block rounded-md bg-sky-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                            Ver Certificado
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center bg-white p-12 rounded-lg shadow-sm">
            <h3 class="text-xl font-medium text-gray-900">Aún no has ganado ningún certificado.</h3>
            <p class="mt-2 text-sm text-gray-500">¡Completa todos los módulos de un curso para obtener tu certificado y verlo aquí!</p>
        </div>
    @endif
@endsection
