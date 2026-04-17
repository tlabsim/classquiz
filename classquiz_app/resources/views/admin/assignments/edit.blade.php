@extends('layouts.admin')

@section('title', 'Edit Assignment')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-40">{{ $quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="hover:text-gray-700 transition-colors">Assignments</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700 truncate max-w-40">{{ $assignment->displayTitle() }}</span>
@endsection

@section('content')
    @include('admin.assignments._form', [
        'quiz' => $quiz,
        'assignment' => $assignment,
        'action' => route('admin.quizzes.assignments.update', [$quiz, $assignment]),
        'method' => 'PUT',
        'title' => 'Edit Assignment',
    ])
@endsection
