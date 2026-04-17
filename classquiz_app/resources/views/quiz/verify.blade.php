@extends('layouts.quiz')

@section('content')
<div class="bg-white rounded-lg shadow p-8">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Enter Your Access Code</h2>
    <p class="text-sm text-gray-500 mb-6">We emailed a 6-character code to your address. Check your inbox (and spam folder).</p>

    <form method="POST" action="{{ route('quiz.verify.submit', $assignment->public_token, false) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="session_id" value="{{ session('session_id') ?? old('session_id') }}">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Access Code</label>
            <input type="text" name="code" maxlength="6" autocomplete="off"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm tracking-widest uppercase font-mono text-center text-lg
                          @error('code') border-red-400 @enderror"
                   placeholder="------">
            @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit"
                class="w-full py-2.5 bg-emerald-600 text-white font-medium rounded-md hover:bg-emerald-700">
            Verify &amp; Start Quiz
        </button>
    </form>

    @if(session('session_id') ?? old('session_id'))
    <form method="POST" action="{{ route('quiz.resend', $assignment->public_token, false) }}" class="mt-4">
        @csrf
        <input type="hidden" name="session_id" value="{{ session('session_id') ?? old('session_id') }}">
        <button type="submit" class="w-full text-sm text-emerald-600 hover:underline">Resend code</button>
    </form>
    @endif
</div>
@endsection
