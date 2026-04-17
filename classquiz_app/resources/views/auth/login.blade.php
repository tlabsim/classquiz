<x-guest-layout>

    <div class="mb-7">
        <h1 class="font-serif text-[1.6rem] text-gray-900 leading-snug">Teacher sign in</h1>
        <p class="mt-1.5 text-sm text-gray-500">Admin and teacher accounts only</p>
    </div>

    {{-- Taker notice --}}
    <div class="mb-6 flex gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span>
            Here to take a quiz?
            <a href="{{ route('home') }}" class="font-medium underline underline-offset-2 hover:text-amber-900 transition-colors">
                Go to the quiz page
            </a>
            &mdash; no account needed.
        </span>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          required autofocus autocomplete="username"
                          class="{{ $errors->has('email') ? 'cq-field-error' : '' }}" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                        Forgot password?
                    </a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password"
                          required autocomplete="current-password"
                          class="{{ $errors->has('password') ? 'cq-field-error' : '' }}" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember"
                   class="h-4 w-4 rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500 cursor-pointer">
            <label for="remember_me" class="text-sm text-gray-600 cursor-pointer select-none">Keep me signed in</label>
        </div>

        <x-primary-button class="w-full mt-1">
            Sign in
        </x-primary-button>
    </form>

</x-guest-layout>
