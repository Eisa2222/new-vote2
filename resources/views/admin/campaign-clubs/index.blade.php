@extends('layouts.admin')

@section('title', __('Club voting links'))
@section('page_title', $campaign->localized('title'))
@section('page_description', __('Each participating club gets its own voting link. Players enter through their club link, pick their name from the dropdown, and vote.'))

@section('content')
<div class="form-wrap space-y-6">
    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn-ghost">
        <span aria-hidden="true">←</span>
        <span>{{ __('Back to campaign') }}</span>
    </a>

    {{-- ── Attach clubs ─────────────────────────────────────────── --}}
    <div class="card space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold">{{ __('Attach clubs') }}</h2>
                <p class="text-sm text-ink-500 mt-1">{{ __('Tick the clubs that will take part in this campaign.') }}</p>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('admin.campaigns.clubs.store', $campaign) }}" class="space-y-4">
            @csrf
            @php $attachedIds = $rows->pluck('club_id')->all(); @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 max-h-64 overflow-y-auto p-3 rounded-xl border border-ink-200">
                @foreach($allClubs as $c)
                    <label class="flex items-center gap-2 rounded-lg p-2 hover:bg-brand-50 cursor-pointer">
                        <input type="checkbox" name="club_ids[]" value="{{ $c->id }}" class="field-checkbox"
                               @checked(in_array($c->id, $attachedIds, true))>
                        <span class="text-sm">{{ $c->localized('name') }}</span>
                    </label>
                @endforeach
            </div>

            <div class="flex items-end gap-3 flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <label class="field-label">{{ __('Max voters per club (optional)') }}</label>
                    <input type="number" name="max_voters" min="1" placeholder="{{ __('Leave empty for unlimited') }}"
                           class="field-input">
                </div>
                <button class="btn-save">
                    <span aria-hidden="true">💾</span>
                    <span>{{ __('Generate / update links') }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── Existing rows ────────────────────────────────────────── --}}
    <div class="card overflow-hidden p-0">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs uppercase">
                <tr>
                    <th class="text-start p-4">{{ __('Club') }}</th>
                    <th class="text-start p-4">{{ __('Voters') }}</th>
                    <th class="text-start p-4">{{ __('Max') }}</th>
                    <th class="text-start p-4">{{ __('Link') }}</th>
                    <th class="text-start p-4">{{ __('Status') }}</th>
                    <th class="text-end p-4">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-ink-50">
                        <td class="p-4 font-medium">{{ $row->club->localized('name') }}</td>
                        <td class="p-4 tabular-nums">{{ $row->current_voters_count }}</td>
                        <td class="p-4 tabular-nums text-ink-500">{{ $row->max_voters ?? '∞' }}</td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <input type="text" readonly value="{{ $row->publicUrl() }}"
                                       class="font-mono text-xs rounded-lg border border-ink-200 bg-ink-50 px-2 py-1 min-w-0 flex-1"
                                       onclick="this.select()">
                                <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $row->publicUrl() }}'); this.textContent='✓'"
                                        class="rounded-lg border border-ink-200 hover:bg-ink-50 px-2 py-1 text-xs">
                                    📋
                                </button>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="badge {{ $row->is_active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $row->is_active ? __('Active') : __('Disabled') }}
                            </span>
                            @if($row->isFull())
                                <span class="ms-1 badge bg-amber-100 text-amber-800">{{ __('Full') }}</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2 justify-end">
                                <form method="post" action="{{ route('admin.campaigns.clubs.update', [$campaign, $row]) }}"
                                      class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <input type="number" name="max_voters" min="1" value="{{ $row->max_voters }}"
                                           placeholder="∞" class="w-16 rounded-lg border border-ink-200 px-2 py-1 text-xs">
                                    <input type="hidden" name="is_active" value="{{ $row->is_active ? 1 : 0 }}">
                                    <button class="rounded-lg border border-ink-200 hover:bg-ink-50 px-2 py-1 text-xs">
                                        💾
                                    </button>
                                </form>
                                <form method="post" action="{{ route('admin.campaigns.clubs.regenerate', [$campaign, $row]) }}"
                                      onsubmit="return confirm('{{ __('Generate a new token? The old link will stop working immediately.') }}')">
                                    @csrf
                                    <button class="rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50 px-2 py-1 text-xs">
                                        🔄 {{ __('New token') }}
                                    </button>
                                </form>
                                <form method="post" action="{{ route('admin.campaigns.clubs.destroy', [$campaign, $row]) }}"
                                      onsubmit="return confirm('{{ __('Remove this club from the campaign?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-danger-500/50 text-danger-600 hover:bg-danger-500/10 px-2 py-1 text-xs">
                                        🗑
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-16 text-center text-ink-400">
                        {{ __('No clubs attached yet. Pick clubs above to generate their voting links.') }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
