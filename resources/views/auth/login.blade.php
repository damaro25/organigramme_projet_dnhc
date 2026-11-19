<x-guest-layout>
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
        <div class="mt-5">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="mt-5 flex flex-wrap items-center justify-between gap-2">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-dnh-950 shadow-sm focus:ring-dnh-500" name="remember">
                <span class="ms-2 text-sm text-slate-700">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="rounded text-sm font-medium text-dnh-700 hover:text-dnh-950 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="mt-7 w-full py-3">
            {{ __('Log in') }}
        </x-primary-button>
    </form>
</x-guest-layout>
