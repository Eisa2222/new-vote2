@extends('layouts.admin')
@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <h1 class="text-2xl font-bold text-ink-900">{{ __('Users') }}</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.users.archive') }}"
           class="btn-ghost">
            <span aria-hidden="true">🗃</span>
            <span>{{ __('Archive') }}</span>
        </a>
        <a href="{{ route('admin.users.create') }}" class="btn-save">
            <span class="text-xl">+</span>
            <span>{{ __('New User') }}</span>
        </a>
    </div>
</div>

{{-- Bulk-archive toolbar — visible only when at least one row is checked. --}}
<x-admin.bulk-toolbar
    :action="route('admin.users.bulkDelete')"
    :confirm-template="__('Archive :n user(s)?')"
    :label="__('Archive selected')"
    color="rose" />

<div class="card overflow-hidden p-0">
    <table class="w-full text-sm">
        <thead class="bg-ink-50 text-ink-500 text-xs uppercase">
            <tr>
                <th class="w-10 p-4">
                    <input type="checkbox" class="bulk-select-all rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                </th>
                <th class="text-start p-4">{{ __('Name') }}</th>
                <th class="text-start p-4">{{ __('Email') }}</th>
                <th class="text-start p-4">{{ __('Roles') }}</th>
                <th class="text-start p-4">{{ __('Status') }}</th>
                <th class="text-start p-4">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
            @foreach($users as $u)
                <tr class="hover:bg-ink-50">
                    <td class="p-4">
                        @if($u->id !== auth()->id())
                            <input type="checkbox" class="bulk-check rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                                   value="{{ $u->id }}" aria-label="{{ __('Select row') }}">
                        @endif
                    </td>
                    <td class="p-4 font-medium">{{ $u->name }}</td>
                    <td class="p-4 text-ink-500">{{ $u->email }}</td>
                    <td class="p-4">
                        @php
                            $roleLabels = [
                                'super_admin'      => __('Super Admin'),
                                'committee'        => __('Voting Committee'),
                                'campaign_manager' => __('Campaign Manager'),
                                'auditor'          => __('Auditor'),
                            ];
                        @endphp
                        @foreach($u->roles as $r)
                            <span class="badge badge-active">{{ $roleLabels[$r->name] ?? $r->name }}</span>
                        @endforeach
                    </td>
                    <td class="p-4">
                        @if(($u->status ?? 'active') === 'active')
                            <span class="badge badge-active">{{ __('Active') }}</span>
                        @else
                            <span class="badge badge-inactive">{{ __('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('admin.users.edit', $u) }}"
                               class="rounded-lg border border-ink-200 hover:bg-ink-50 px-3 py-1.5 text-xs font-medium">
                                ✏️ {{ __('Edit') }}
                            </a>
                            @if($u->id !== auth()->id())
                                <form method="post" action="{{ route('admin.users.toggle', $u) }}">
                                    @csrf
                                    <button class="rounded-lg border border-warning-500/50 text-warning-500 hover:bg-warning-500/10 px-3 py-1.5 text-xs font-medium">
                                        @if(($u->status ?? 'active') === 'active')
                                            ⏸ {{ __('Disable') }}
                                        @else
                                            ▶ {{ __('Enable') }}
                                        @endif
                                    </button>
                                </form>
                                <form method="post" action="{{ route('admin.users.destroy', $u) }}"
                                      onsubmit="return confirm('{{ __('Archive this user? They can be restored from the archive later.') }}')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-danger-500/50 text-danger-600 hover:bg-danger-500/10 px-3 py-1.5 text-xs font-medium">
                                        🗑 {{ __('Archive') }}
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-ink-500">{{ __('You') }}</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
