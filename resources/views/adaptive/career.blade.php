@extends('layouts.platform')

@section('content')
@php($photoUrl = $profile->photo_path ? Storage::disk('public')->url($profile->photo_path) : null)
<div class="mx-auto w-full max-w-6xl">
    <div class="flex flex-col justify-between gap-5 border-b border-slate-200 pb-6 lg:flex-row lg:items-end">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Presenta tu trayectoria con claridad</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">Organiza la información que ayuda a identificar tu objetivo, experiencia y preparación para nuevas oportunidades.</p>
        </div>
        <div class="flex flex-col items-start gap-2 lg:items-end">
            @if($profile->is_public)
                <a href="{{ route('adaptive.public-profile', $profile->public_slug) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm font-bold text-blue-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">
                    Ver perfil público
                    <span aria-hidden="true">↗</span>
                </a>
                <form method="POST" action="{{ route('adaptive.career.public-link') }}">@csrf<button class="text-xs font-semibold text-slate-500 underline hover:text-slate-800">Crear un enlace nuevo (el actual dejará de funcionar)</button></form>
            @else
                <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">Tu perfil público está desactivado.</p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('adaptive.career.update') }}" enctype="multipart/form-data" class="mt-7 space-y-6" x-data="profileEditor(@js(old('education', $profile->education ?? [])), @js(old('work_experience', $profile->work_experience ?? [])), @js($photoUrl))">
        @csrf
        @method('PATCH')
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <label class="flex items-start gap-3">
                <input type="hidden" name="is_public" value="0">
                <input type="checkbox" name="is_public" value="1" class="mt-1 rounded border-slate-300 text-blue-600" @checked(old('is_public', $profile->is_public))>
                <span>
                    <span class="block font-bold text-slate-900">Activar mi perfil público</span>
                    <span class="mt-1 block text-sm leading-6 text-slate-500">Quien tenga tu enlace podrá ver tu nombre, foto, LinkedIn, rutas completadas y las secciones que marques como visibles. El enlace es privado: no aparece en buscadores ni se puede adivinar.</span>
                </span>
            </label>
        </section>

        <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-sm">
            <div class="border-b border-blue-100 bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 px-6 py-7 text-white sm:px-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    <div class="relative shrink-0">
                        <template x-if="photoPreview && !removePhoto">
                            <img :src="photoPreview" alt="Vista previa de tu foto" class="h-24 w-24 rounded-2xl border-4 border-white/30 object-cover shadow-lg">
                        </template>
                        <template x-if="!photoPreview || removePhoto">
                            <img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="Perfil de {{ auth()->user()->name }}" class="h-24 w-24 rounded-2xl border-4 border-white/20 bg-white/10 object-cover shadow-lg">
                        </template>
                        <span class="absolute -bottom-2 -right-2 grid h-8 w-8 place-items-center rounded-full border-2 border-blue-900 bg-emerald-400 text-xs font-black text-emerald-950" title="Perfil editable">✓</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-2xl font-extrabold">{{ auth()->user()->name }}</h2>
                        <p class="mt-1 text-sm text-blue-100">{{ $profile->professional_goal ?: 'Agrega tu objetivo profesional para completar tu presentación.' }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.55fr)] sm:p-8">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Foto de perfil</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Opcional. Usa una imagen clara y profesional.</p>
                        </div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-500">JPG, PNG o WebP</span>
                    </div>
                    <label class="mt-5 flex cursor-pointer items-center justify-center rounded-xl border border-dashed border-blue-300 bg-white px-4 py-4 text-center text-sm font-bold text-blue-700 transition hover:border-blue-500 hover:bg-blue-50">
                        <span x-text="photoPreview && !removePhoto ? 'Cambiar foto' : 'Subir foto'"></span>
                        <input x-ref="photoInput" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="selectPhoto($event)">
                    </label>
                    @error('profile_photo')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
                    @if($profile->photo_path)
                        <label class="mt-3 flex items-center gap-2 text-sm font-medium text-slate-600"><input type="checkbox" name="remove_photo" value="1" x-model="removePhoto" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Quitar foto actual</label>
                    @endif
                    <p class="mt-3 text-xs leading-5 text-slate-500">Máximo 2 MB. La foto se muestra en tu perfil público.</p>
                </div>

                <div>
                    <div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-100 text-sm font-extrabold text-blue-800">01</span><div><h2 class="font-extrabold text-slate-900">Información esencial</h2><p class="text-sm text-slate-500">Lo primero que verá una persona al conocer tu perfil.</p></div></div>
                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="professional_goal" class="text-sm font-bold text-slate-800">Objetivo profesional</label>
                            <input id="professional_goal" name="professional_goal" value="{{ old('professional_goal', $profile->professional_goal) }}" class="mt-2 block w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej. Analista de datos junior">
                            @error('professional_goal')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="linkedin_url" class="text-sm font-bold text-slate-800">Perfil de LinkedIn <span class="font-medium text-slate-500">(opcional)</span></label>
                            <input id="linkedin_url" type="url" name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" class="mt-2 block w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="https://linkedin.com/in/tu-perfil">
                            @error('linkedin_url')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start">
                <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-100 text-sm font-extrabold text-blue-800">02</span><div><h2 class="font-extrabold text-slate-900">Resumen profesional</h2><p class="mt-1 text-sm leading-6 text-slate-500">Describe fortalezas, intereses y el tipo de oportunidad que buscas.</p></div></div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">Visible en el perfil público <select name="bio_visibility" class="rounded-lg border-slate-200 py-1.5 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="private" @selected(old('bio_visibility', $profile->bio_visibility) === 'private')>No</option><option value="public" @selected(old('bio_visibility', $profile->bio_visibility) === 'public')>Sí</option></select></label>
            </div>
            <textarea name="bio" rows="5" class="mt-5 block w-full rounded-xl border-slate-200 text-sm leading-6 focus:border-blue-500 focus:ring-blue-500" placeholder="Ej. Me interesa usar datos y herramientas digitales para resolver problemas y aportar a equipos de trabajo...">{{ old('bio', $profile->bio) }}</textarea>
            @error('bio')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start">
                    <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-100 text-sm font-extrabold text-blue-800">03</span><div><h2 class="font-extrabold text-slate-900">Escolaridad</h2><p class="mt-1 text-sm leading-6 text-slate-500">Carreras, estudios técnicos, cursos o certificaciones.</p></div></div>
                    <select name="education_visibility" aria-label="Visibilidad de escolaridad" class="rounded-lg border-slate-200 py-1.5 text-sm font-semibold text-slate-600 focus:border-blue-500 focus:ring-blue-500"><option value="private" @selected(old('education_visibility', $profile->education_visibility) === 'private')>Privado</option><option value="public" @selected(old('education_visibility', $profile->education_visibility) === 'public')>Público</option></select>
                </div>
                <div class="mt-5 space-y-3">
                    <template x-if="education.length === 0"><p class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">Aún no agregas estudios. Incluye los más relevantes para tu objetivo.</p></template>
                    <template x-for="(item, index) in education" :key="'education-' + index">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3"><p class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500" x-text="'Estudio ' + (index + 1)"></p><button type="button" @click="education.splice(index, 1)" class="text-sm font-bold text-rose-600 hover:text-rose-700">Quitar</button></div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2"><input x-model="item.program" :name="'education[' + index + '][program]'" class="rounded-xl border-slate-200 text-sm" placeholder="Carrera, título o curso"><input x-model="item.institution" :name="'education[' + index + '][institution]'" class="rounded-xl border-slate-200 text-sm" placeholder="Institución educativa"></div>
                            <input x-model="item.period" :name="'education[' + index + '][period]'" class="mt-3 w-full rounded-xl border-slate-200 text-sm" placeholder="Periodo, por ejemplo: 2022 - 2025">
                        </div>
                    </template>
                </div>
                <button type="button" @click="education.push({institution: '', program: '', period: ''})" class="mt-5 rounded-xl border border-blue-200 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-50">+ Agregar estudio</button>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start">
                    <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-100 text-sm font-extrabold text-blue-800">04</span><div><h2 class="font-extrabold text-slate-900">Experiencia</h2><p class="mt-1 text-sm leading-6 text-slate-500">Empleo, prácticas, voluntariado o proyectos relevantes.</p></div></div>
                    <select name="work_experience_visibility" aria-label="Visibilidad de experiencia" class="rounded-lg border-slate-200 py-1.5 text-sm font-semibold text-slate-600 focus:border-blue-500 focus:ring-blue-500"><option value="private" @selected(old('work_experience_visibility', $profile->work_experience_visibility) === 'private')>Privado</option><option value="public" @selected(old('work_experience_visibility', $profile->work_experience_visibility) === 'public')>Público</option></select>
                </div>
                <div class="mt-5 space-y-3">
                    <template x-if="experience.length === 0"><p class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">Aún no agregas experiencia. También puedes incluir proyectos personales.</p></template>
                    <template x-for="(item, index) in experience" :key="'experience-' + index">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3"><p class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500" x-text="'Experiencia ' + (index + 1)"></p><button type="button" @click="experience.splice(index, 1)" class="text-sm font-bold text-rose-600 hover:text-rose-700">Quitar</button></div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2"><input x-model="item.role" :name="'work_experience[' + index + '][role]'" class="rounded-xl border-slate-200 text-sm" placeholder="Cargo o función"><input x-model="item.company" :name="'work_experience[' + index + '][company]'" class="rounded-xl border-slate-200 text-sm" placeholder="Empresa u organización"></div>
                            <input x-model="item.period" :name="'work_experience[' + index + '][period]'" class="mt-3 w-full rounded-xl border-slate-200 text-sm" placeholder="Periodo, por ejemplo: 2025 - Actual">
                            <textarea x-model="item.description" :name="'work_experience[' + index + '][description]'" rows="3" class="mt-3 w-full rounded-xl border-slate-200 text-sm" placeholder="Responsabilidades, resultados o herramientas utilizadas"></textarea>
                        </div>
                    </template>
                </div>
                <button type="button" @click="experience.push({role: '', company: '', period: '', description: ''})" class="mt-5 rounded-xl border border-blue-200 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-50">+ Agregar experiencia</button>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.45fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-100 text-sm font-extrabold text-blue-800">05</span><div><h2 class="font-extrabold text-slate-900">Currículum vitae</h2><p class="mt-1 text-sm leading-6 text-slate-500">Tu PDF es privado y solo tú puedes verlo.</p></div></div>
                <input type="file" name="cv" accept="application/pdf" class="mt-5 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-blue-700 hover:file:bg-blue-100">
                @error('cv')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
                @if($profile->cv_path)<a class="mt-4 inline-flex text-sm font-bold text-blue-700 hover:text-blue-900" href="{{ Storage::disk('public')->url($profile->cv_path) }}" target="_blank" rel="noopener noreferrer">Ver CV actual ↗</a>@endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                <div class="flex items-center justify-between gap-4"><div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-sm font-extrabold text-emerald-800">✓</span><div><h2 class="font-extrabold text-slate-900">Rutas completadas</h2><p class="mt-1 text-sm leading-6 text-slate-500">Logros generados automáticamente por Aprende Guate.</p></div></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-extrabold text-emerald-700">{{ $completedPaths->count() }}</span></div>
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @forelse($completedPaths as $path)<article class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4"><p class="font-bold text-slate-800">{{ $path->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $path->goal }}</p><p class="mt-3 text-xs font-bold text-emerald-700">Completada {{ $path->updated_at->format('d/m/Y') }}</p></article>@empty<p class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500 md:col-span-2">Cuando completes una ruta, aparecerá aquí como logro.</p>@endforelse
                </div>
            </section>
        </div>

        <div class="profile-save-bar sticky bottom-4 z-20 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">Los campos marcados como públicos se mostrarán en tu perfil compartible.</p>
            <button class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white shadow-md shadow-blue-700/20 transition hover:bg-blue-800">Guardar cambios</button>
        </div>
    </form>
</div>

<script>
    window.profileEditor = (education, experience, photoUrl) => ({
        education: education || [],
        experience: experience || [],
        photoPreview: photoUrl,
        removePhoto: false,
        selectPhoto(event) {
            const [file] = event.target.files || [];
            if (!file) return;
            this.photoPreview = URL.createObjectURL(file);
            this.removePhoto = false;
        },
    });
</script>
@endsection
