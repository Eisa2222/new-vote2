@extends('layouts.admin')
@section('content')
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-ink-900">{{ __('Users') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.archive') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-ink-200 hover:bg-ink-50 text-ink-700 px-4 py-2 text-sm font-medium transition">
                🗃 {{ __('Archive') }}
            </a>
            <a href="{{ route('admin.users.create') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 text-sm font-semibold shadow-sm transition flex-shrink-0">
                <span>+</span>
                {{ __('New User') }}
            </a>
        </div>
    </div>

    <x-admin.bulk-toolbar :action="route('admin.users.bulkDelete')" :confirm-template="__('Archive :n user(s)?')" :label="__('Archive selected')" color="rose" />

    <div data-datatable-scope class="bg-white border border-ink-200 rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-ink-100">
            <x-admin.datatable-head :search-placeholder="__('Search by name or email...')" />
        </div>
        {{-- overflow-x-auto keeps the wide (avatar+name+email+roles+
             status+actions) table from forcing horizontal scroll on
             the whole page when the viewport is tablet-portrait. --}}
        <div class="overflow-x-auto">
            <table data-datatable class="w-full text-sm min-w-[820px]">
                <thead>
                    <tr class="bg-ink-50 border-b border-ink-200">
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox"
                                class="bulk-select-all rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        </th>
                        <th data-sort
                            class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">
                            {{ __('Name') }}</th>
                        <th data-sort
                            class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">
                            {{ __('Email') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">
                            {{ __('Roles') }}</th>
                        <th data-sort
                            class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">
                            {{ __('Status') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">
                            {{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @foreach ($users as $u)
                        <tr class="hover:bg-ink-50 transition-colors">
                            <td class="px-4 py-3">
                                @if ($u->id !== auth()->id())
                                    <input type="checkbox"
                                        class="bulk-check rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                                        value="{{ $u->id }}" aria-label="{{ __('Select row') }}">
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                        {{ mb_strtoupper(mb_substr($u->name, 0, 2)) }}
                                    </div>
                                    <span class="font-medium text-ink-900">{{ $u->name }}</span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-ink-500">{{ $u->email }}</td>

                            <td class="px-4 py-3">
                                @php
                                    $roleLabels = [
                                        'super_admin' => __('Super Admin'),
                                        'committee' => __('Voting Committee'),
                                        'campaign_manager' => __('Campaign Manager'),
                                        'auditor' => __('Auditor'),
                                    ];
                                @endphp
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($u->roles as $r)
                                        <span
                                            class="inline-flex items-center rounded-full bg-brand-50 text-brand-700 px-2.5 py-0.5 text-xs font-medium">
                                            {{ $roleLabels[$r->name] ?? $r->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <x-admin.status-chip :status="$u->status ?? 'active'" dot />
                            </td>

                            <td class="px-4 py-3">
                                @if ($u->id !== auth()->id())
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.users.edit', $u) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-ink-200 hover:bg-ink-50 px-3 py-1.5 text-xs font-medium text-ink-700 transition">
                                            ✏️ {{ __('Edit') }}
                                        </a>
                                        <form method="post" action="{{ route('admin.users.toggle', $u) }}">
                                            @csrf
                                            <button
                                                class="inline-flex items-center gap-1 rounded-lg border border-amber-200 hover:bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition">
                                                @if (($u->status ?? 'active') === 'active')
                                                    ⏸ {{ __('Disable') }}
                                                @else
                                                    ▶ {{ __('Enable') }}
                                                @endif
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('admin.users.destroy', $u) }}"
                                            onsubmit="return confirm('{{ __('Archive this user? They can be restored from the archive later.') }}')">
                                            @csrf @method('DELETE')
                                            <button
                                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 hover:bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-600 transition">
                                                🗑 {{ __('Archive') }}
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-ink-100 text-ink-500 px-2.5 py-0.5 text-xs">
                                        {{ __('You') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
