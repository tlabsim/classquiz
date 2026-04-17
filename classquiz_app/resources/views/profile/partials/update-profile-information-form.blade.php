<section>
    @php
        $timezones = collect(timezone_identifiers_list())
            ->map(function ($timezone) {
                $offsetMinutes = now($timezone)->utcOffset();
                $sign = $offsetMinutes >= 0 ? '+' : '-';
                $absoluteMinutes = abs($offsetMinutes);
                $hours = intdiv($absoluteMinutes, 60);
                $minutes = $absoluteMinutes % 60;
                $offsetLabel = $minutes === 0
                    ? "UTC{$sign}{$hours}"
                    : sprintf('UTC%s%d:%02d', $sign, $hours, $minutes);

                return [
                    'timezone' => $timezone,
                    'offset' => $offsetMinutes,
                    'label' => "{$timezone} ({$offsetLabel})",
                ];
            })
            ->sortBy([
                ['offset', 'asc'],
                ['timezone', 'asc'],
            ])
            ->values();
    @endphp

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account name and default timezone.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full bg-gray-50 text-gray-500" :value="old('email', $user->email)" required readonly autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
            <p class="mt-1.5 text-sm text-gray-500">Email changes are disabled from this page.</p>
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Default Timezone')" />
            <select id="timezone" name="timezone" class="cq-field mt-1 block w-full text-sm">
                @foreach ($timezones as $timezone)
                    <option value="{{ $timezone['timezone'] }}" @selected(old('timezone', $user->timezone()) === $timezone['timezone'])>
                        {{ $timezone['label'] }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
