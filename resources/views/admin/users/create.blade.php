@extends('layouts.admin')
@section('content')
    <h1 class="text-2xl font-bold mb-6 text-slate-800">{{ __('New User') }}</h1>

    <form method="post" action="{{ route('admin.users.create') }}"
        class="bg-white rounded-2xl shadow p-6 md:p-8 space-y-5 form-wrap">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Name') }}</label>
            <input name="name" value="{{ old('name', $user->name) }}" required class="w-full border rounded-lg px-3 py-2">
            @error('name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                class="w-full border rounded-lg px-3 py-2">
            @error('email')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                {{ __('Password') }}
                <span class="text-slate-400 text-xs">({{ __('leave empty to send an invitation email') }})</span>
            </label>
            <input type="password" name="password" class="w-full border rounded-lg px-3 py-2" autocomplete="new-password"
                placeholder="{{ __('Leave blank — we will email them a setup link') }}">
            @error('password')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-ink-500">
                {{ __('If you leave this blank, the user gets an invitation email with a one-time link to choose their own password.') }}
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('Roles') }} <span class="text-rose-600">*</span>
                <span class="text-slate-400 text-xs font-normal">({{ __('at least one required') }})</span>
            </label>
            @error('roles')
                <p class="text-rose-600 text-sm mb-2">{{ $message }}</p>
            @enderror
            @php
                $roleLabels = [
                    'super_admin' => __('Super Admin'),
                    'committee' => __('Voting Committee'),
                    'campaign_manager' => __('Campaign Manager'),
                    'auditor' => __('Auditor'),
                ];
                $roleHints = [
                    'super_admin' => __('Full access to every section.'),
                    'committee' => __('Sees campaigns and approves results only.'),
                    'campaign_manager' => __('Manages clubs, players and creates campaigns.'),
                    'auditor' => __('Read-only observer.'),
                ];
            @endphp
            <div class="space-y-2">
                @foreach ($roles as $r)
                    <label
                        class="flex items-start gap-3 rounded-xl border border-ink-200 p-3 cursor-pointer hover:bg-ink-50">
                        <input type="checkbox" name="roles[]" value="{{ $r->name }}" class="mt-1"
                            @checked(in_array($r->name, old('roles', $user->roles->pluck('name')->all())))>
                        <span>
                            <span class="font-semibold">{{ $roleLabels[$r->name] ?? $r->name }}</span>
                            @if (!empty($roleHints[$r->name]))
                                <span class="block text-xs text-ink-500 mt-0.5">{{ $roleHints[$r->name] }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </div>


        <div class="sticky bottom-0 bg-white pt-4 border-t border-ink-200 flex gap-3 items-center justify-end"> <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                <span>{{ __('Save') }}</span>
            </button>
            <a href="{{ route('admin.users.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-ink-200 hover:bg-ink-50 text-ink-700 px-5 py-2.5 text-sm font-medium transition">
                {{ __('Cancel') }}
            </a>

        </div>

    </form>
@endsection
