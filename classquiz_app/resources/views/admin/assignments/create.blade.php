@extends('layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="text-sm text-emerald-600 hover:underline">← Assignments</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-2">New Assignment</h1>
</div>

<form method="POST" action="{{ route('admin.quizzes.assignments.store', $quiz) }}"
      class="max-w-2xl bg-white rounded-lg shadow p-6 space-y-5">
    @csrf

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Custom Title <span class="text-gray-400 font-normal">(optional – defaults to quiz title)</span></label>
        <input type="text" name="title" value="{{ old('title') }}"
               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
        <textarea name="instructions" rows="4"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('instructions') }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Registration Opens</label>
            <input type="datetime-local" name="registration_start" value="{{ old('registration_start') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Registration Closes</label>
            <input type="datetime-local" name="registration_end" value="{{ old('registration_end') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Available From</label>
            <input type="datetime-local" name="availability_start" value="{{ old('availability_start') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Available Until</label>
            <input type="datetime-local" name="availability_end" value="{{ old('availability_end') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
        <input type="number" name="duration_minutes" value="{{ old('duration_minutes') }}" min="1"
               class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Unlimited">
    </div>

    {{-- Settings --}}
    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">Settings</label>
        @foreach([
            'allow_resume'        => 'Allow students to resume if they close the browser',
            'show_score'          => 'Show score to student after submission',
            'randomize_questions' => 'Randomize question order',
        ] as $key => $label)
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="settings[{{ $key }}]" value="0">
            <input type="checkbox" name="settings[{{ $key }}]" value="1"
                   {{ old("settings.$key", \App\Models\QuizAssignment::SETTINGS_DEFAULTS[$key] ?? false) ? 'checked' : '' }}>
            {{ $label }}
        </label>
        @endforeach
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-700">Max attempts:</label>
            <input type="number" name="settings[max_attempts]" value="{{ old('settings.max_attempts', 1) }}"
                   min="1" class="w-20 border border-gray-300 rounded-md px-2 py-1 text-sm">
        </div>
    </div>

    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" checked>
        <label for="is_active" class="text-sm text-gray-700">Active (accessible to students)</label>
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Cancel</a>
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">Create Assignment</button>
    </div>
</form>
@endsection
