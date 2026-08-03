@extends('layouts.platform')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <img class="rounded-lg shadow-lg mb-6 w-full h-auto object-cover" src="{{ $course->cover_image_url ?? 'https://via.placeholder.com/800x450.png/007bff/ffffff?text=Curso' }}" alt="Imagen del curso {{ $course->title }}">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-4">{{ $course->title }}</h1>
            <p class="text-lg text-gray-700 mb-6">{{ $course->description }}</p>

            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Contenido del Curso</h2>
            <div class="space-y-4">
                @forelse($course->modules as $module)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-lg text-gray-800">{{ $module->name }}</h3>
                        @if($module->classes->count() > 0)
                            <ul class="mt-2 list-disc list-inside text-gray-600">
                                @foreach($module->classes as $class)
                                    <li>{{ $class->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">El contenido de este curso estará disponible pronto.</p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-10">
                <h3 class="text-xl font-semibold mb-2">Instructor</h3>
                <p class="text-gray-700 mb-6">{{ $course->instructor->name ?? 'No asignado' }}</p>

                {{-- Botón de Inscripción Inteligente --}}
                @auth
                    @if(auth()->user()->courses->contains($course))
                        <a href="{{ route('student.course.study', $course) }}" class="w-full text-center block rounded-md bg-green-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                            Ir al Curso
                        </a>
                    @else
                        <form action="{{ route('student.course.enroll', $course) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-center block rounded-md bg-sky-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                                Inscribirse Ahora
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="w-full text-center block rounded-md bg-sky-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                        Regístrate para Inscribirte
                    </a>
                @endauth
            </div>
        </div>
    </div>
@endsection
