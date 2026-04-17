@extends('layouts.admin')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="text-sm text-emerald-600 hover:underline">← {{ $quiz->title }}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Assignments</h1>
    </div>
    <a href="{{ route('admin.quizzes.assignments.create', $quiz) }}"
       class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">+ New Assignment</a>
</div>

<div class="space-y-4">
    @forelse($assignments as $assignment)
    <div class="bg-white rounded-lg shadow p-4 flex items-start justify-between gap-4">
        <div>
            <p class="font-medium text-gray-900">{{ $assignment->displayTitle() }}</p>
            <p class="text-xs text-gray-400 mt-1">
                Token: <code>{{ $assignment->public_token }}</code>
                &bull;
                <span class="{{ $assignment->is_active ? 'text-green-600' : 'text-gray-400' }}">
                    {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                </span>
            </p>
        </div>
        <div class="flex items-center gap-3 text-sm shrink-0">
            <a href="{{ route('admin.quizzes.assignments.show', [$quiz, $assignment]) }}" class="text-emerald-600 hover:underline">View</a>
            <a href="{{ route('admin.quizzes.assignments.report', [$quiz, $assignment]) }}" class="text-emerald-600 hover:underline">Report</a>
            <a href="{{ route('admin.quizzes.assignments.edit', [$quiz, $assignment]) }}" class="text-gray-500 hover:underline">Edit</a>
            <form method="POST" action="{{ route('admin.quizzes.assignments.destroy', [$quiz, $assignment]) }}"
                  onsubmit="return confirm('Delete this assignment?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-500 hover:underline">Delete</button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-400">
        No assignments yet. <a href="{{ route('admin.quizzes.assignments.create', $quiz) }}" class="text-emerald-600 hover:underline">Create one.</a>
    </div>
    @endforelse
</div>
@endsection
