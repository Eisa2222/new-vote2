@extends('layouts.admin')

@section('title', __('Profile'))
@section('page_title', __('My profile'))
@section('page_description', __('Update your personal details and password'))

@section('content')
@php($dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr')

<div class="form-wrap space-y-6">
    {{-- Personal details --}}
    <div class="card space-y-5">
        <div>
            <h2 class="text-xl font-bold">{{ __('Personal details') }}</h2>
            <p class="text-sm text-ink-500 mt-1">{{ __('Name and email shown across the platform.') }}</p>
        </div>

        @if ($errors->updateProfile->any())
            <div class="rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm">
                {{ $errors->updateProfile->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('profile.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Name') }}</label>
                <input name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       autocomplete="email"
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="md:col-span-2 flex items-center gap-3">
                <button class="btn-save">
                    <span>{{ __('Save') }}</span>
                </button>
                @if($user->roles->isNotEmpty())
                    <span class="text-xs text-ink-500">
                        {{ __('Role') }}:
                        <strong class="text-ink-800">{{ $user->roles->pluck('name')->implode(', ') }}</strong>
                    </span>
                @endif
            </div>
        </form>
    </div>

    {{-- Change password --}}
    <div class="card space-y-5" x-data="{ show: false }">
        <div>
            <h2 class="text-xl font-bold">{{ __('Change password') }}</h2>
            <p class="text-sm text-ink-500 mt-1">{{ __('Minimum 10 characters with upper & lower case, numbers and a symbol.') }}</p>
        </div>

        @if (session('success') && str_contains(session('success'), __('Password')))
            <div class="rounded-2xl bg-brand-50 border border-brand-200 text-brand-800 p-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="post" action="{{ route('profile.password') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Current password') }}</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('New password') }}</label>
                <input :type="show ? 'text' : 'password'" name="password" required autocomplete="new-password"
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Confirm password') }}</label>
                <input :type="show ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="md:col-span-3 flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm text-ink-700">
                    <input type="checkbox" x-model="show" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    <span>{{ __('Show password') }}</span>
                </label>
                <button class="btn-save">
                    <span aria-hidden="true">🔑</span>
                    <span>{{ __('Change password') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
