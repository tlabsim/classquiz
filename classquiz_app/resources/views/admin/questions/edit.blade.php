@extends('layouts.admin')

@section('title', 'Edit Question')
@section('suppress_admin_alerts', '1')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-40">{{ $quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="hover:text-gray-700 transition-colors">Questions</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700">Edit</span>
@endsection

@section('content')
    @include('admin.questions._form', [
        'question' => $question,
        'action' => route('admin.quizzes.questions.update', [$quiz, $question]),
        'method' => 'PUT',
        'title' => 'Edit Question',
        'submitLabel' => 'Save changes',
    ])
@endsection
