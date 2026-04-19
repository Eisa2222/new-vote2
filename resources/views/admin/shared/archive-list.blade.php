@extends('layouts.admin')

@php($archiveLabel = __(ucfirst($module)))
@section('title', __('Archive — :label', ['label' => $archiveLabel]))
@section('page_title', __('Archive — :label', ['label' => $archiveLabel]))
@section('page_description', __('Restore or permanently delete archived items.'))

@section('content')
@php($canForce = auth()->user()?->can($module.'.forceDelete'))

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.archive') }}" class="btn-ghost">
            <span aria-hidden="true">←</span>
            <span>{{ __('All archives') }}</span>
        </a>
        <a href="{{ route($backRoute) }}" class="btn-ghost">
            <span aria-hidden="true">📋</span>
            <span>{{ __('Active list') }}</span>
        </a>
    </div>
</div>

<div data-datatable-scope class="card overflow-hidden p-0">
    <div class="p-4 border-b border-ink-100">
        <x-admin.datatable-head />
    </div>
    <table data-datatable class="w-full text-sm">
        <thead class="bg-ink-50 text-ink-500 text-xs uppercase">
            <tr>
                <th data-sort="number" class="text-start p-4">#</th>
                <th data-sort class="text-start p-4">{{ __('Label') }}</th>
                <th data-sort="date" class="text-start p-4">{{ __('Archived at') }}</th>
                <th class="text-end p-4">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
            @forelse($rows as $row)
                <tr class="hover:bg-ink-50">
                    <td class="p-4 text-ink-500 tabular-nums">{{ $row->id }}</td>
                    <td class="p-4 font-medium">
                        @if(method_exists($row, 'localized'))
                            {{ $row->localized('title') ?? $row->localized('name') ?? ('#'.$row->id) }}
                        @else
                            {{ $row->name_en ?? $row->title_en ?? $row->email ?? $row->name ?? ('#'.$row->id) }}
                        @endif
                    </td>
                    <td class="p-4 text-ink-500">{{ $row->deleted_at?->diffForHumans() }}</td>
                    <td class="p-4">
                        <div class="flex items-center gap-2 justify-end">
                            <form method="post" action="{{ route($restoreRoute, $row->id) }}">
                                @csrf
                                <button class="rounded-lg border border-brand-500/50 text-brand-700 hover:bg-brand-500/10 px-3 py-1.5 text-xs font-medium">
                                    ↩ {{ __('Restore') }}
                                </button>
                            </form>
                            @if($canForce)
                                <form method="post" action="{{ route($forceRoute, $row->id) }}"
                                      onsubmit="return confirm('{{ __('Permanently delete this item? This cannot be undone.') }}')">
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
                <tr><td colspan="4" class="py-12 text-center text-ink-400">{{ __('No archived items.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
