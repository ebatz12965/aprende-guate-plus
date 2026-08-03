@extends('layouts.platform')

@section('content')
    <div class="flex flex-col lg:flex-row gap-8">
        <div class="lg:w-1/3 text-center">
            <img class="w-48 h-48 rounded-full mx-auto mb-4 shadow-lg" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&size=256&background=random" alt="Avatar de {{ $user->name }}">
            <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
            {{-- Podríamos añadir un campo 'bio' al modelo User en el futuro --}}
            <p class="text-gray-600 mt-4">Apasionado por la enseñanza y el desarrollo de aplicaciones web robustas y escalables. En mi tiempo libre, disfruto contribuyendo a proyectos de código abierto y explorando nuevas tecnologías.</p>
        </div>

        <div class="lg:w-2/3">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Cursos Impartidos</h2>
            <div class="space-y-6">
                @forelse ($user->taughtCourses as $course)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden flex transform hover:-translate-y-1 transition-transform duration-300">
                        <img class="h-32 w-1/3 object-cover" src="{{ $course->cover_image_url ?? 'https://via.placeholder.com/400x250.png/007bff/ffffff?text=Curso' }}" alt="Imagen del curso {{ $course->title }}">
                        <div class="p-4 flex-1">
                            <h3 class="text-lg font-semibold mb-1">{{ $course->title }}</h3>
                            <p class="text-gray-600 text-sm mb-3">{{ Str::limit($course->description, 120) }}</p>
                            <a href="{{ route('public.course.detail', $course) }}" class="text-sm font-semibold text-sky-600 hover:text-sky-500">
                                Ver curso
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center bg-gray-50 p-8 rounded-lg">
                        <p class="text-gray-500">Este instructor aún no ha publicado ningún curso.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
