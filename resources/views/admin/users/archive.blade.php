@extends('layouts.admin')

@section('title', __('Users — Archive'))
@section('page_title', __('Users archive'))
@section('page_description', __('Archived users can be restored or permanently deleted.'))

@section('content')
@php($canForce = auth()->user()?->can('users.forceDelete'))

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <a href="{{ route('admin.users.index') }}" class="btn-ghost">
        <span aria-hidden="true">←</span>
        <span>{{ __('Back to users') }}</span>
    </a>
</div>

{{-- Two toolbars: one restores, one permanently deletes (super-admin only). --}}
<div x-data class="space-y-2 mb-2">
    <x-admin.bulk-toolbar
        :action="route('admin.users.bulkRestore')"
        :confirm-template="__('Restore :n user(s) from archive?')"
        :label="__('Restore selected')"
        color="brand" />

    @if($canForce)
        <x-admin.bulk-toolbar
            :action="route('admin.users.bulkForceDelete')"
            :confirm-template="__('Permanently delete :n user(s)? This cannot be undone.')"
            :label="__('Permanently delete selected')"
            color="rose" />
    @endif
</div>

<div class="card overflow-hidden p-0">
    <table class="w-full text-sm">
        <thead class="bg-ink-50 text-ink-500 text-xs uppercase">
            <tr>
                <th class="w-10 p-4">
                    <input type="checkbox" class="bulk-select-all rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                </th>
                <th class="text-start p-4">{{ __('Name') }}</th>
                <th class="text-start p-4">{{ __('Email') }}</th>
                <th class="text-start p-4">{{ __('Archived at') }}</th>
                <th class="text-start p-4">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
            @forelse($users as $u)
                <tr class="hover:bg-ink-50">
                    <td class="p-4">
                        <input type="checkbox" class="bulk-check rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                               value="{{ $u->id }}" aria-label="{{ __('Select row') }}">
                    </td>
                    <td class="p-4 font-medium">{{ $u->name }}</td>
                    <td class="p-4 text-ink-500">{{ $u->email }}</td>
                    <td class="p-4 text-ink-500">{{ $u->deleted_at?->diffForHumans() }}</td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            <form method="post" action="{{ route('admin.users.restore', $u->id) }}">
                                @csrf
                                <button class="rounded-lg border border-brand-500/50 text-brand-700 hover:bg-brand-500/10 px-3 py-1.5 text-xs font-medium">
                                    ↩ {{ __('Restore') }}
                                </button>
                            </form>
                            @if($canForce)
                                <form method="post" action="{{ route('admin.users.forceDelete', $u->id) }}"
                                      onsubmit="return confirm('{{ __('Permanently delete this user? This cannot be undone.') }}')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-danger-500/50 text-danger-600 hover:bg-danger-500/10 px-3 py-1.5 text-xs font-medium">
                                        🗑 {{ __('Delete forever') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-12 text-center text-ink-400">{{ __('No archived users.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
