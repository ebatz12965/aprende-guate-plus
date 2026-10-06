{{-- Cambio de espacio (aprendizaje, mentor, administración). Se usa en el menú superior y en el modo de menú lateral. --}}
@if ($canMentorSpace || $canAdminSpace)
    <div class="relative" x-data="{ spacesOpen: false }" @keydown.escape="spacesOpen = false">
        <button type="button" @click="spacesOpen = !spacesOpen" @click.outside="spacesOpen = false" :aria-expanded="spacesOpen" aria-haspopup="menu"
            class="platform-nav-link inline-flex items-center gap-2" aria-label="Cambiar espacio">
            <span class="grid h-7 w-7 place-items-center rounded-lg bg-blue-100 text-blue-700" aria-hidden="true">⇄</span>
            <span class="hidden lg:inline">{{ $activeSpace === 'admin' ? 'Administración' : ($activeSpace === 'mentor' ? 'Espacio mentor' : 'Mi aprendizaje') }}</span>
        </button>
        <div x-cloak x-show="spacesOpen" x-transition role="menu"
            class="absolute right-0 top-full z-40 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            @foreach (['learning' => ['Mi aprendizaje', true], 'mentor' => ['Espacio mentor', $canMentorSpace], 'admin' => ['Administración', $canAdminSpace]] as $space => [$label, $allowed])
                @if ($allowed && $activeSpace !== $space)
                    <form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space" value="{{ $space }}"><button class="platform-menu-link w-full text-left" role="menuitem">{{ $label }}</button></form>
                @endif
            @endforeach
        </div>
    </div>
@endif
