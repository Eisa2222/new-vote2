@extends('layouts.admin')

@section('title', __('Roles & permissions'))
@section('page_title', __('Roles & permissions'))
@section('page_description', __('Create roles and control exactly what each role can do.'))

@section('content')
    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
        <div class="text-sm text-ink-500">
            {{ __(':n roles configured', ['n' => $roles->count()]) }}
        </div>
        <a href="{{ route('admin.roles.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 text-sm font-semibold shadow-sm transition flex-shrink-0">
            <span>+</span>
            <span>{{ __('New role') }}</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($roles as $role)
            @php
                $builtIn = in_array($role->name, ['super_admin', 'committee', 'campaign_manager', 'auditor'], true);
                $localised =
                    [
                        'super_admin' => __('Super Admin'),
                        'committee' => __('Voting Committee'),
                        'campaign_manager' => __('Campaign Manager'),
                        'auditor' => __('Auditor'),
                    ][$role->name] ?? $role->name;
            @endphp
            <div class="rounded-3xl bg-white border border-ink-200 shadow-sm p-5 flex flex-col">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-extrabold text-ink-900 text-lg truncate">{{ $localised }}</div>
                        <div class="text-xs text-ink-500 font-mono mt-0.5">{{ $role->name }}</div>
                    </div>
                    @if ($builtIn)
                        <span
                            class="inline-flex items-center rounded-full bg-brand-100 text-brand-700 px-2 py-0.5 text-[10px] font-bold flex-shrink-0">
                            {{ __('Built-in') }}
                        </span>
                    @endif
                </div>

                <div class="mt-3 flex items-center gap-2 text-xs text-ink-500">
                    <span>👥
                        {{ trans_choice('{1} :n user|[2,*] :n users', $role->users_count, ['n' => $role->users_count]) }}</span>
                    <span>·</span>
                    <span>🔑 {{ __(':n permissions', ['n' => $role->permissions->count()]) }}</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach ($role->permissions->take(6) as $permission)
                        <span class="inline-block rounded bg-ink-100 text-ink-700 px-2 py-0.5 text-[10px] font-mono">
                            {{ $permission->name }}
                        </span>
                    @endforeach
                    @if ($role->permissions->count() > 6)
                        <span class="inline-block rounded bg-ink-100 text-ink-500 px-2 py-0.5 text-[10px]">
                            +{{ $role->permissions->count() - 6 }}
                        </span>
                    @endif
                </div>

                <div class="mt-auto pt-4 flex items-center gap-2">
                    <a href="{{ route('admin.roles.edit', $role) }}"
                        class="flex-1 text-center rounded-xl border border-ink-200 hover:border-brand-400 px-3 py-2 text-sm font-semibold text-ink-700">
                        ✏️ {{ __('Edit') }}
                    </a>
                    @if (!$builtIn)
                        <form method="post" action="{{ route('admin.roles.destroy', $role) }}"
                            onsubmit="return confirm('{{ __('Delete this role?') }}')">
                            @csrf @method('DELETE')
                            <button
                                class="rounded-xl border border-rose-300 text-rose-700 hover:bg-rose-50 px-3 py-2 text-sm font-semibold">
                                🗑 {{ __('Delete') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
