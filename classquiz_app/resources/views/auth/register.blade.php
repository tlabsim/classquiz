<x-guest-layout>

    <div class="mb-7">
        <h1 class="font-serif text-[1.6rem] text-gray-900 leading-snug">Create your account</h1>
        <p class="mt-1.5 text-sm text-gray-500">Join ClassQuiz as a teacher</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')"
                          required autofocus autocomplete="name"
                          class="{{ $errors->has('name') ? 'cq-field-error' : '' }}" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          required autocomplete="username"
                          class="{{ $errors->has('email') ? 'cq-field-error' : '' }}" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password"
                          required autocomplete="new-password"
                          class="{{ $errors->has('password') ? 'cq-field-error' : '' }}" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation"
                          required autocomplete="new-password"
                          class="{{ $errors->has('password_confirmation') ? 'cq-field-error' : '' }}" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full mt-1">
            Create account
        </x-primary-button>
    </form>

    <p class="mt-7 text-center text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-emerald-600 hover:text-emerald-700 transition-colors">Sign in</a>
    </p>

</x-guest-layout>
