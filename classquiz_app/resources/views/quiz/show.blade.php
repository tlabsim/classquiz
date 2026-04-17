@extends('layouts.quiz')

@section('content')
<div class="bg-white rounded-lg shadow p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $assignment->displayTitle() }}</h2>

    @if($assignment->instructions)
    <div class="mt-3 mb-6 p-4 bg-emerald-50 rounded-md text-sm text-emerald-800">
        {{ $assignment->instructions }}
    </div>
    @endif

    @if(!$assignment->isRegistrationOpen())
    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md text-yellow-800 text-sm">
        Registration is currently closed for this quiz.
    </div>
    @else
    <form method="POST" action="{{ route('quiz.register', $assignment->public_token) }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('email') border-red-400 @enderror">
            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Class / Section</label>
            <input type="text" name="class_id" value="{{ old('class_id') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>
        <button type="submit"
                class="w-full py-2.5 bg-emerald-600 text-white font-medium rounded-md hover:bg-emerald-700">
            Register &amp; Get Access Code
        </button>
    </form>
    @endif
</div>
@endsection
