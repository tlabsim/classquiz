@extends('layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.import') }}" class="text-sm text-emerald-600 hover:underline">← Back</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-2">Import Quiz from JSON</h1>
</div>

<form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data"
      class="max-w-lg bg-white rounded-lg shadow p-6 space-y-5">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">JSON File <span class="text-red-500">*</span></label>
        <input type="file" name="file" accept=".json"
               class="w-full text-sm @error('file') border-red-400 @enderror">
        @error('file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="flex justify-end">
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">Import</button>
    </div>
</form>
@endsection
