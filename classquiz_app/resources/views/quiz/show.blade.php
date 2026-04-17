@extends('layouts.quiz')

@section('content')
@php
    $emailValue = old('email', $currentSession?->email);
    $nameValue = old('name', $currentSession?->name);
    $classIdValue = old('class_id', $currentSession?->class_id);
    $sessionIdValue = old('session_id', $currentSession?->id);
    $accessCodeOpen = $assignment->isRegistrationOpen();
    $canAttemptDirectly = !$assignment->access_code_required && $assignment->isAvailable();
    $enabledQuestions = $assignment->quiz->questions;
    $questionCount = $enabledQuestions->count();
    $totalPoints = $enabledQuestions->sum('points');
@endphp

<div class="bg-white rounded-lg shadow p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $assignment->displayTitle() }}</h2>

    <div class="mt-4 mb-6 flex flex-wrap gap-2">
        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $questionCount }} {{ \Illuminate\Support\Str::plural('question', $questionCount) }}</span>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ rtrim(rtrim(number_format((float) $totalPoints, 2, '.', ''), '0'), '.') }} pts.</span>
        @if($assignment->duration_minutes)
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $assignment->duration_minutes }} min</span>
        @else
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">No time limit</span>
        @endif
        @if($assignment->availability_start)
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">From {{ $assignment->displayDateTime($assignment->availability_start) }}</span>
        @endif
        @if($assignment->availability_end)
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">Until {{ $assignment->displayDateTime($assignment->availability_end) }}</span>
        @endif
    </div>

    @if($assignment->instructions)
        <div class="mt-3 mb-6 p-4 bg-emerald-50 rounded-md text-sm text-emerald-800">
            <div class="cq-richtext">{!! $assignment->instructions !!}</div>
        </div>
    @endif

    <div class="mb-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
        @if($assignment->access_code_required)
            <p class="font-medium text-gray-800">Access code required</p>
            <p class="mt-1">
                @if($assignment->availability_start)
                    Access code pickup opens {{ $assignment->displayDateTime($assignment->accessCodeOpensAt()) }}.
                @else
                    Access code pickup is available immediately.
                @endif
            </p>
        @else
            <p class="font-medium text-gray-800">Direct start enabled</p>
            <p class="mt-1">Complete the required details below, then start the quiz when you are ready.</p>
        @endif
        <p class="mt-2 text-xs text-gray-500">Reading these instructions does not start the timer. The quiz begins only after you press Start the quiz.</p>
    </div>

    @if($assignment->access_code_required && !$accessCodeOpen)
        <div class="mb-6 rounded-md border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
            Access code pickup is currently closed for this quiz.
        </div>
    @elseif(!$assignment->access_code_required && !$assignment->isAvailable())
        <div class="mb-6 rounded-md border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
            This quiz is not currently available.
        </div>
    @endif

    @if($readyToStart && $currentSession)
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-sm font-semibold text-emerald-900">Everything is ready.</p>
            <p class="mt-1 text-sm text-emerald-800">
                Your details{{ $assignment->access_code_required ? ' and access code' : '' }} have been confirmed. Start the quiz when you are ready.
            </p>

            <div class="mt-4 flex flex-wrap gap-2 text-xs text-emerald-900">
                <span class="rounded-full bg-white px-3 py-1 ring-1 ring-emerald-200">{{ $currentSession->email }}</span>
                @if($assignment->setting('collect_name', false) && $currentSession->name)
                    <span class="rounded-full bg-white px-3 py-1 ring-1 ring-emerald-200">{{ $currentSession->name }}</span>
                @endif
                @if($assignment->setting('collect_class_id', false) && $currentSession->class_id)
                    <span class="rounded-full bg-white px-3 py-1 ring-1 ring-emerald-200">{{ $currentSession->class_id }}</span>
                @endif
            </div>

            <form method="POST" action="{{ route('quiz.start', $assignment->public_token, false) }}" class="mt-4">
                @csrf
                <input type="hidden" name="session_id" value="{{ $currentSession->id }}">
                <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-700">
                    Start the quiz
                </button>
            </form>
        </div>
    @endif

    @unless($readyToStart)
        <form method="POST" action="{{ route('quiz.register', $assignment->public_token, false) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="session_id" value="{{ $sessionIdValue }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ $emailValue }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('email') border-red-400 @enderror">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            @if($assignment->setting('collect_name', false))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ $nameValue }}"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            @endif

            @if($assignment->setting('collect_class_id', false))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class ID</label>
                    <input type="text" name="class_id" value="{{ $classIdValue }}"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('class_id') border-red-400 @enderror">
                    @error('class_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            @endif

            @if($assignment->access_code_required)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Access Code</label>
                    <input type="text" name="code" maxlength="6" autocomplete="off" value="{{ old('code') }}"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm tracking-widest uppercase font-mono text-center text-lg @error('code') border-red-400 @enderror"
                           placeholder="------">
                    @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            @endif

            <button type="submit"
                    class="w-full py-2.5 bg-emerald-600 text-white font-medium rounded-md hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                    @disabled($assignment->access_code_required ? !$accessCodeOpen : !$canAttemptDirectly)>
                {{ $assignment->access_code_required ? 'Verify access code' : 'Continue' }}
            </button>

            @if($assignment->access_code_required)
                <div class="border-t border-gray-100 pt-5">
                    <p class="text-sm text-gray-600">Don’t have an access code yet?</p>
                    @if($accessCodeOpen)
                        <button type="submit"
                                formaction="{{ route('quiz.request-code', $assignment->public_token, false) }}"
                                formmethod="POST"
                                class="mt-3 w-full rounded-md border border-emerald-200 bg-white px-4 py-3 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                            Email me a new access code
                        </button>
                    @else
                        <p class="mt-2 text-sm text-yellow-700">Access code pickup is not open yet.</p>
                    @endif
                </div>
            @endif
        </form>
    @endunless
</div>
@endsection
