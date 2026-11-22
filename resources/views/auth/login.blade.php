<x-guest-layout>
    <h1 class="font-display text-2xl font-semibold uppercase tracking-titre text-primary-950">Connexion</h1>
    <p class="mb-6 mt-1 text-sm text-slate-600">Accédez à l'espace d'administration avec votre compte.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-primary-950 shadow-sm focus:ring-primary-600" name="remember">
                <span class="ms-2 text-sm text-slate-700">{{ __('Remember me') }}</span>
            </label>
        </div>

        <x-primary-button class="mt-6 w-full py-2.5">
            {{ __('Log in') }}
        </x-primary-button>

        @if (Route::has('password.request'))
            <p class="mt-4 text-center">
                <a class="text-sm font-medium text-primary-700 underline-offset-2 hover:text-primary-950 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            </p>
        @endif
    </form>
</x-guest-layout>
