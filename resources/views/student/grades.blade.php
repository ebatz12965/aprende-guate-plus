@extends('layouts.platform')

@section('content')
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-8">
        Mis Calificaciones
    </h2>

    <div class="space-y-12">
        @forelse ($coursesWithGrades as $courseData)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-semibold">{{ $courseData['course']->title }}</h3>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Promedio del Curso</p>
                            <p class="text-2xl font-bold text-sky-600">{{ number_format($courseData['average_grade'], 2) }}</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarea</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calificación</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($courseData['submissions'] as $submission)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('student.task.view', $submission->task) }}" class="text-sm font-medium text-sky-600 hover:text-sky-900">
                                            {{ $submission->task->title }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $submission->grade ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $submission->grade ? 'Calificado' : 'Entregado' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $submission->grade ? number_format($submission->grade, 2) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="text-center bg-white p-12 rounded-lg shadow-sm">
                <h3 class="text-xl font-medium text-gray-900">Aún no tienes calificaciones.</h3>
                <p class="mt-2 text-sm text-gray-500">Completa y entrega las tareas de tus cursos para ver tu progreso aquí.</p>
            </div>
        @endforelse
    </div>
@endsection
