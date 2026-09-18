@extends('layouts.admin')

@section('content')
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-8">
        Añadir Clase a "{{ $module->name }}"
    </h2>

    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form action="{{ route('admin.modules.classes.store', $module) }}" method="POST" enctype="multipart/form-data" data-video-upload-form>
            @csrf
            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre de la Clase</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" required>
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Contenido de la Clase (Texto)</label>
                    <textarea name="content" id="content" rows="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"></textarea>
                </div>

                <div>
                    <label for="video_file" class="block text-sm font-medium text-gray-700">Subir video de la clase</label>
                    <input type="file" name="video_file" id="video_file" accept="video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <p class="mt-2 text-sm text-gray-500">MP4, MOV, AVI, MKV o WebM. Máximo 2 GB. El video se procesará automáticamente en 360p, 480p y 720p.</p>
                    @error('video_file')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <label for="video_url" class="block text-sm font-medium text-gray-700">O URL de YouTube (opcional)</label>
                    <input type="url" name="video_url" id="video_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" placeholder="https://www.youtube.com/watch?v=...">
                    <p class="mt-2 text-sm text-gray-500">Usa esta opción solo si no vas a cargar un archivo desde tu computadora.</p>
                </div>

            </div>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('admin.modules.show', $module) }}" class="rounded-md bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Cancelar</a>
                <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Guardar Clase</button>
            </div>
        </form>
    </div>
@endsection
