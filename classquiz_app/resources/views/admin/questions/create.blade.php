@extends('layouts.admin')

@section('title', 'Add Question')
@section('suppress_admin_alerts', '1')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-40">{{ $quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="hover:text-gray-700 transition-colors">Questions</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700">Add</span>
@endsection

@section('content')
    @include('admin.questions._form', [
        'action' => route('admin.quizzes.questions.store', $quiz),
        'title' => 'Add Question',
        'submitLabel' => 'Save question',
    ])
@endsection
