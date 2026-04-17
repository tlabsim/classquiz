@extends('layouts.admin')

@section('title', 'New Quiz')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700">New</span>
@endsection

@section('content')
    @include('admin.quizzes._form', [
        'action' => route('admin.quizzes.store'),
        'title' => 'New Quiz',
    ])
@endsection
