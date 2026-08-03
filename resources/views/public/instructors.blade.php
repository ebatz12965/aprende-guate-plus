@extends('layouts.platform')

@section('content')
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-8">
        Nuestros Instructores
    </h2>

    @if($instructors->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($instructors as $instructor)
                <div class="bg-white rounded-lg shadow-md overflow-hidden text-center">
                    <img class="h-48 w-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode($instructor->name) }}&size=256&background=random" alt="Avatar de {{ $instructor->name }}">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2">{{ $instructor->name }}</h3>
                        {{-- Podríamos añadir un campo 'bio' al modelo User en el futuro --}}
                        <p class="text-gray-600 mb-4">Especialista en desarrollo de aplicaciones web.</p>
                        <a href="{{ route('public.instructor.profile', $instructor) }}" class="text-sm font-semibold text-sky-600 hover:text-sky-500">
                            Ver Perfil Completo
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center bg-white p-12 rounded-lg shadow-sm">
            <h3 class="text-xl font-medium text-gray-900">No hay instructores disponibles en este momento.</h3>
        </div>
    @endif
@endsection
