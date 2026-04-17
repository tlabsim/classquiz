@extends('layouts.admin')

@section('title', 'Quiz Settings')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-48">{{ $quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700">Settings</span>
@endsection

@section('content')
    @include('admin.quizzes._form', [
        'quiz' => $quiz,
        'action' => route('admin.quizzes.update', $quiz),
        'method' => 'PUT',
        'title' => 'Quiz Settings',
    ])
@endsection
