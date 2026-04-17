@extends('layouts.admin')

@section('title', 'Profile')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700 transition-colors">Dashboard</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700">Profile</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="cq-page-title">Profile</h1>
            <p class="mt-1 text-sm text-gray-500">Update your account details, default timezone, and password.</p>
        </div>

        <div class="cq-card p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="cq-card p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="cq-card p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
