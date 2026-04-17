@extends('layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="text-sm text-emerald-600 hover:underline">← Assignments</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $assignment->displayTitle() }}</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Public link --}}
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <h2 class="font-semibold text-gray-800 mb-3">Student Link</h2>
        <div class="flex items-center gap-2">
            <input type="text" value="{{ $publicUrl }}" readonly
                   class="flex-1 border border-gray-200 rounded-md px-3 py-2 text-sm bg-gray-50 font-mono">
            <button onclick="navigator.clipboard.writeText('{{ $publicUrl }}')"
                    class="px-3 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Copy</button>
        </div>
        @if($assignment->instructions)
        <div class="mt-4">
            <p class="text-sm font-medium text-gray-700 mb-1">Instructions:</p>
            <p class="text-sm text-gray-600">{{ $assignment->instructions }}</p>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="space-y-3">
        <a href="{{ route('admin.quizzes.assignments.edit', [$quiz, $assignment]) }}"
           class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Edit Assignment</a>
        <a href="{{ route('admin.quizzes.assignments.report', [$quiz, $assignment]) }}"
           class="block w-full text-center px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">View Report</a>
    </div>
</div>
@endsection
