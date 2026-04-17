@extends('layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.quizzes.index') }}" class="text-sm text-emerald-600 hover:underline">← Back to Quizzes</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-2">New Quiz</h1>
</div>

<form method="POST" action="{{ route('admin.quizzes.store') }}" class="max-w-2xl bg-white rounded-lg shadow p-6 space-y-5">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title') }}"
               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 @error('title') border-red-400 @enderror">
        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" rows="4"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">{{ old('description') }}</textarea>
    </div>
    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.quizzes.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Cancel</a>
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">Create Quiz</button>
    </div>
</form>
@endsection
