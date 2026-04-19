@extends('layouts.admin')
@section('content')
    <h1 class="text-2xl font-bold mb-6 text-slate-800">{{ $user->exists ? __('Edit User') : __('New User') }}</h1>

    {{-- Wider form: max-w-6xl fills modern laptop screens without becoming
         uncomfortably wide on 4K displays. Use a two-column grid on md+. --}}
    <form method="post" action="{{ $user->exists ? '/admin/users/'.$user->id : '/admin/users' }}"
          class="bg-white rounded-2xl shadow p-6 md:p-8 space-y-5 form-wrap">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Name') }}</label>
            <input name="name" value="{{ old('name', $user->name) }}" required class="w-full border rounded-lg px-3 py-2">
            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border rounded-lg px-3 py-2">
            @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                {{ __('Password') }}
                @if($user->exists)
                    <span class="text-slate-400 text-xs">({{ __('leave empty to keep current') }})</span>
                @else
                    <span class="text-slate-400 text-xs">({{ __('leave empty to send an invitation email') }})</span>
                @endif
            </label>
            <input type="password" name="password" class="w-full border rounded-lg px-3 py-2"
                   autocomplete="new-password"
                   placeholder="{{ !$user->exists ? __('Leave blank — we will email them a setup link') : '••••••••' }}">
            @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            @if(!$user->exists)
                <p class="mt-1.5 text-xs text-ink-500">
                    💌 {{ __('If you leave this blank, the user gets an invitation email with a one-time link to choose their own password.') }}
                </p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('Roles') }} <span class="text-rose-600">*</span>
                <span class="text-slate-400 text-xs font-normal">({{ __('at least one required') }})</span>
            </label>
            @error('roles') <p class="text-rose-600 text-sm mb-2">{{ $message }}</p> @enderror
            @php
                $roleLabels = [
                    'super_admin'      => __('Super Admin'),
                    'committee'        => __('Voting Committee'),
                    'campaign_manager' => __('Campaign Manager'),
                    'auditor'          => __('Auditor'),
                ];
                $roleHints = [
                    'super_admin'      => __('Full access to every section.'),
                    'committee'        => __('Sees campaigns and approves results only.'),
                    'campaign_manager' => __('Manages clubs, players and creates campaigns.'),
                    'auditor'          => __('Read-only observer.'),
                ];
            @endphp
            <div class="space-y-2">
                @foreach($roles as $r)
                    <label class="flex items-start gap-3 rounded-xl border border-ink-200 p-3 cursor-pointer hover:bg-ink-50">
                        <input type="checkbox" name="roles[]" value="{{ $r->name }}" class="mt-1"
                               @checked(in_array($r->name, old('roles', $user->roles->pluck('name')->all())))>
                        <span>
                            <span class="font-semibold">{{ $roleLabels[$r->name] ?? $r->name }}</span>
                            @if(!empty($roleHints[$r->name]))
                                <span class="block text-xs text-ink-500 mt-0.5">{{ $roleHints[$r->name] }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="sticky bottom-0 bg-white pt-4 border-t flex gap-2 items-center">
            <button class="btn-save">
                <span aria-hidden="true">💾</span>
                <span>{{ __('Save') }}</span>
            </button>
            <a href="/admin/users" class="btn-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
