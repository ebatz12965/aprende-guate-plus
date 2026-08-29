@extends('layouts.platform')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-bold text-indigo-600">PASO 6 · PREPARACIÓN LABORAL</p>
            <h1 class="mt-2 text-3xl font-extrabold">Tu perfil profesional</h1>
            <p class="mt-2 text-slate-500">Construye una presentación clara de tu trayectoria, lista para oportunidades laborales.</p>
        </div>
        <a href="{{ route('adaptive.public-profile', auth()->user()) }}" target="_blank" class="rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-center text-sm font-bold text-indigo-700 hover:bg-indigo-50">Ver perfil público</a>
    </div>

    <form method="POST" action="{{ route('adaptive.career.update') }}" enctype="multipart/form-data" class="mt-7 space-y-6" x-data="profileEditor(@js(old('education', $profile->education ?? [])), @js(old('work_experience', $profile->work_experience ?? [])))">
        @csrf
        @method('PATCH')

        <section class="platform-feature">
            <div class="flex items-center justify-between gap-4">
                <div><h2 class="!mt-0 text-xl">Información principal</h2><p class="text-sm text-slate-500">Lo esencial para identificar tu perfil profesional.</p></div>
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">Perfil Aprende Guate</span>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div><label class="text-sm font-bold">Objetivo profesional</label><input name="professional_goal" value="{{ old('professional_goal', $profile->professional_goal) }}" class="mt-2 w-full rounded-xl border-slate-200" placeholder="Ej. Analista de datos junior"></div>
                <div><label class="text-sm font-bold">URL de LinkedIn</label><input type="url" name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" class="mt-2 w-full rounded-xl border-slate-200" placeholder="https://linkedin.com/in/tu-perfil"></div>
            </div>
        </section>

        <section class="platform-feature">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                <div><h2 class="!mt-0 text-xl">Sobre mí</h2><p class="text-sm text-slate-500">Resume tus fortalezas, intereses y el tipo de oportunidad que buscas.</p></div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-600"><span>Visibilidad</span><select name="bio_visibility" class="rounded-lg border-slate-200 py-1.5 text-sm"><option value="private" @selected(old('bio_visibility', $profile->bio_visibility) === 'private')>Privado</option><option value="public" @selected(old('bio_visibility', $profile->bio_visibility) === 'public')>Público</option></select></label>
            </div>
            <textarea name="bio" rows="5" class="mt-4 w-full rounded-xl border-slate-200" placeholder="Ej. Me interesa usar datos y herramientas digitales para resolver problemas...">{{ old('bio', $profile->bio) }}</textarea>
        </section>

        <section class="platform-feature">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                <div><h2 class="!mt-0 text-xl">Escolaridad</h2><p class="text-sm text-slate-500">Agrega estudios, carreras, cursos técnicos o certificaciones relevantes.</p></div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-600"><span>Visibilidad</span><select name="education_visibility" class="rounded-lg border-slate-200 py-1.5 text-sm"><option value="private" @selected(old('education_visibility', $profile->education_visibility) === 'private')>Privado</option><option value="public" @selected(old('education_visibility', $profile->education_visibility) === 'public')>Público</option></select></label>
            </div>
            <div class="mt-5 space-y-4">
                <template x-for="(item, index) in education" :key="'education-' + index">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid gap-3 md:grid-cols-3">
                            <input x-model="item.institution" :name="'education[' + index + '][institution]'" class="rounded-xl border-slate-200" placeholder="Institución educativa">
                            <input x-model="item.program" :name="'education[' + index + '][program]'" class="rounded-xl border-slate-200" placeholder="Carrera, título o curso">
                            <div class="flex gap-2"><input x-model="item.period" :name="'education[' + index + '][period]'" class="min-w-0 flex-1 rounded-xl border-slate-200" placeholder="Ej. 2022 - 2025"><button type="button" @click="education.splice(index, 1)" class="rounded-xl border border-rose-200 px-3 text-sm font-bold text-rose-600">Quitar</button></div>
                        </div>
                    </div>
                </template>
            </div>
            <button type="button" @click="education.push({institution: '', program: '', period: ''})" class="mt-4 rounded-xl border border-indigo-200 px-4 py-2 text-sm font-bold text-indigo-700 hover:bg-indigo-50">+ Agregar estudio</button>
        </section>

        <section class="platform-feature">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                <div><h2 class="!mt-0 text-xl">Experiencia laboral</h2><p class="text-sm text-slate-500">Incluye empleo formal, prácticas, voluntariado o proyectos relevantes.</p></div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-600"><span>Visibilidad</span><select name="work_experience_visibility" class="rounded-lg border-slate-200 py-1.5 text-sm"><option value="private" @selected(old('work_experience_visibility', $profile->work_experience_visibility) === 'private')>Privado</option><option value="public" @selected(old('work_experience_visibility', $profile->work_experience_visibility) === 'public')>Público</option></select></label>
            </div>
            <div class="mt-5 space-y-4">
                <template x-for="(item, index) in experience" :key="'experience-' + index">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid gap-3 md:grid-cols-3">
                            <input x-model="item.role" :name="'work_experience[' + index + '][role]'" class="rounded-xl border-slate-200" placeholder="Cargo o función">
                            <input x-model="item.company" :name="'work_experience[' + index + '][company]'" class="rounded-xl border-slate-200" placeholder="Empresa u organización">
                            <div class="flex gap-2"><input x-model="item.period" :name="'work_experience[' + index + '][period]'" class="min-w-0 flex-1 rounded-xl border-slate-200" placeholder="Ej. 2025 - Actual"><button type="button" @click="experience.splice(index, 1)" class="rounded-xl border border-rose-200 px-3 text-sm font-bold text-rose-600">Quitar</button></div>
                        </div>
                        <textarea x-model="item.description" :name="'work_experience[' + index + '][description]'" rows="3" class="mt-3 w-full rounded-xl border-slate-200" placeholder="Responsabilidades, resultados o herramientas utilizadas"></textarea>
                    </div>
                </template>
            </div>
            <button type="button" @click="experience.push({role: '', company: '', period: '', description: ''})" class="mt-4 rounded-xl border border-indigo-200 px-4 py-2 text-sm font-bold text-indigo-700 hover:bg-indigo-50">+ Agregar experiencia</button>
        </section>

        <section class="platform-feature">
            <h2 class="!mt-0 text-xl">Currículum vitae</h2>
            <p class="mt-1 text-sm text-slate-500">Tu archivo PDF se mantiene privado y sólo tú puedes consultarlo desde esta sección.</p>
            <input type="file" name="cv" accept="application/pdf" class="mt-4 block w-full text-sm">
            @if($profile->cv_path)<a class="mt-3 inline-block text-sm font-bold text-indigo-700" href="{{ Storage::disk('public')->url($profile->cv_path) }}" target="_blank">Ver CV actual</a>@endif
        </section>

        <section class="platform-feature">
            <div class="flex items-center justify-between gap-4"><div><h2 class="!mt-0 text-xl">Rutas completadas</h2><p class="text-sm text-slate-500">Tus logros de aprendizaje se muestran automáticamente en tu perfil público.</p></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-extrabold text-emerald-700">{{ $completedPaths->count() }}</span></div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @forelse($completedPaths as $path)<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4"><p class="font-bold text-slate-800">{{ $path->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $path->goal }}</p><p class="mt-2 text-xs font-bold text-emerald-700">Completada {{ $path->updated_at->format('d/m/Y') }}</p></div>@empty<p class="text-sm text-slate-500">Cuando apruebes todos los mini-quizzes de una ruta, aparecerá aquí.</p>@endforelse
            </div>
        </section>

        <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-md shadow-indigo-600/20 hover:bg-indigo-700">Guardar perfil profesional</button>
    </form>
</div>

<script>
    window.profileEditor = (education, experience) => ({
        education: education.length ? education : [{ institution: '', program: '', period: '' }],
        experience: experience.length ? experience : [{ role: '', company: '', period: '', description: '' }],
    });
</script>
@endsection
