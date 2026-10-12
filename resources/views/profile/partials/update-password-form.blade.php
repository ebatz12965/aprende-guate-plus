<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    @php($googleOnly = ! auth()->user()->has_local_password)
    @php($googleConfirmed = $googleOnly && \App\Http\Controllers\SocialiteController::recentlyConfirmed(request()))
    @if ($googleOnly && ! $googleConfirmed)
        <div class="mt-6 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">
            <p><strong>Tu cuenta se creó con Google</strong> y todavía no tiene una contraseña propia. Créala para poder activar la verificación en dos pasos.</p>
            <p class="mt-1">Para protegerte, primero confirma que eres tú con tu cuenta de Google.</p>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            <a href="{{ route('socialite.confirm') }}" class="mt-3 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Confirmar con Google</a>
        </div>
    @else
    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        @if ($googleOnly)
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">Identidad confirmada con Google. Crea tu contraseña.</p>
        @else
        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>
        @endif

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
    @endif
</section>
