@extends('layouts.admin')

@section('title', __('Club voting links'))
@section('page_title', __('Club voting links'))
@section('page_description', __('Each participating club gets its own voting link. Players enter through their club link, pick their name from the dropdown, and vote.'))

@section('content')
{{--
  Designed to mirror the polished admin/users/index layout so every
  admin list feels the same: breadcrumb-style header → configuration
  card → datatable-wrapped table with search + sortable columns +
  status chips + avatar bubbles + compact row actions.
--}}
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <div class="text-[11px] text-ink-500 font-semibold uppercase tracking-wider">{{ __('Campaign') }}</div>
        <h1 class="text-2xl font-bold text-ink-900 mt-0.5">{{ $campaign->localized('title') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.campaigns.show', $campaign) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-ink-200 hover:bg-ink-50 text-ink-700 px-4 py-2 text-sm font-medium transition">
            <span aria-hidden="true">←</span>
            <span>{{ __('Back to campaign') }}</span>
        </a>
    </div>
</div>

{{-- ── Attach-clubs panel ─────────────────────────────────────── --}}
<div class="bg-white border border-ink-200 rounded-2xl p-5 mb-5">
    <div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
        <div>
            <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                <span>🔗</span>
                <span>{{ __('Attach clubs') }}</span>
            </h2>
            <p class="text-sm text-ink-500 mt-1">{{ __('Tick the clubs that will take part in this campaign.') }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-700 p-3 text-sm mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    @php $attachedIds = $rows->pluck('club_id')->all(); @endphp

    {{-- Alpine controller: `leagueFilter` is the currently-chosen
         league id (or '') and decides which club cards show below.
         `clubLeagues` is the (club_id → [league_id...]) map so a
         club that plays in multiple leagues is visible under each. --}}
    <form method="post" action="{{ route('admin.campaigns.clubs.store', $campaign) }}" class="space-y-4"
          x-data='{
              leagueFilter: "",
              clubLeagues: @json($clubLeagues),
              visible(clubId) {
                  if (!this.leagueFilter) return true;
                  const lids = this.clubLeagues[clubId] || [];
                  return lids.map(String).includes(String(this.leagueFilter));
              },
              selectAllVisible() {
                  document.querySelectorAll("input[data-club-check]")
                      .forEach(cb => {
                          const id = cb.value;
                          if (this.visible(id)) cb.checked = true;
                      });
              },
              clearVisible() {
                  document.querySelectorAll("input[data-club-check]")
                      .forEach(cb => {
                          const id = cb.value;
                          if (this.visible(id)) cb.checked = false;
                      });
              },
          }'>
        @csrf

        {{-- League filter row — single wide select + bulk controls.
             Narrows the grid below without reloading the page. --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1.5 text-ink-800 flex items-center gap-1.5">
                    <span aria-hidden="true">🏆</span>
                    <span>{{ __('Filter by league') }}</span>
                </label>
                <select x-model="leagueFilter"
                        class="w-full rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                    <option value="">— {{ __('All leagues') }} —</option>
                    @foreach($leagues as $l)
                        <option value="{{ $l->id }}">{{ app()->getLocale() === 'ar' ? $l->name_ar : $l->name_en }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink-500">{{ __('Pick a league to narrow the list below, then tick the clubs you want.') }}</p>
            </div>

            <div class="flex items-center gap-2 justify-end">
                <button type="button" @click="selectAllVisible()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-brand-300 text-brand-700 bg-brand-50 hover:bg-brand-100 px-3 py-2 text-xs font-semibold transition">
                    ✓ {{ __('Select visible') }}
                </button>
                <button type="button" @click="clearVisible()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-ink-200 text-ink-700 hover:bg-ink-50 px-3 py-2 text-xs font-medium transition">
                    ✕ {{ __('Clear visible') }}
                </button>
            </div>
        </div>

        {{-- Clubs grid.
             `x-show="visible(...)"` means clubs filter in real time as
             the league dropdown changes; nothing reloads. --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 max-h-64 overflow-y-auto p-3 rounded-xl border border-ink-200 bg-ink-50/40">
            @foreach($allClubs as $c)
                <label x-show="visible('{{ $c->id }}')"
                       class="flex items-center gap-2 rounded-lg p-2 bg-white hover:bg-brand-50 border border-transparent hover:border-brand-200 cursor-pointer transition">
                    <input type="checkbox" name="club_ids[]" value="{{ $c->id }}" data-club-check
                           class="rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                           @checked(in_array($c->id, $attachedIds, true))>
                    <span class="text-sm text-ink-800 truncate">{{ $c->localized('name') }}</span>
                </label>
            @endforeach

            {{-- Empty state when the filter returns nothing --}}
            <template x-if="leagueFilter && {{ $allClubs->filter(fn($c) => isset($clubLeagues[$c->id]))->count() }} > 0 && !document.querySelectorAll('input[data-club-check]').length">
                <div class="col-span-full text-center text-ink-500 py-6 text-sm">
                    {{ __('No clubs in this league.') }}
                </div>
            </template>
        </div>

        <div class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[240px] max-w-sm">
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Max voters per club (optional)') }}</label>
                <input type="number" name="max_voters" min="1"
                       placeholder="{{ __('Leave empty for unlimited') }}"
                       class="w-full rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                <p class="mt-1 text-xs text-ink-500">{{ __('Only applied to newly-added clubs; existing rows keep their own value.') }}</p>
            </div>
            <button class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                <span aria-hidden="true">🔗</span>
                <span>{{ __('Generate / update links') }}</span>
            </button>
        </div>
    </form>
</div>

{{-- ── Rows table (same skeleton as admin/users/index) ────────── --}}
<div data-datatable-scope class="bg-white border border-ink-200 rounded-2xl overflow-hidden">
    <div class="p-4 border-b border-ink-100 flex items-center justify-between gap-3 flex-wrap">
        <x-admin.datatable-head :search-placeholder="__('Search by club name...')" />
        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 text-brand-700 px-2.5 py-1 font-semibold">
                {{ $rows->count() }} {{ __('clubs') }}
            </span>
            <span class="inline-flex items-center gap-1 rounded-full bg-ink-100 text-ink-700 px-2.5 py-1 font-semibold tabular-nums">
                {{ $rows->sum('current_voters_count') }} {{ __('votes') }}
            </span>
        </div>
    </div>

    <table data-datatable class="w-full text-sm">
        <thead>
            <tr class="bg-ink-50 border-b border-ink-200">
                <th data-sort class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">{{ __('Club') }}</th>
                <th data-sort="number" class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">{{ __('Voters') }}</th>
                <th data-sort="number" class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">{{ __('Max') }}</th>
                <th class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">{{ __('Voting link') }}</th>
                <th data-sort class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">{{ __('Status') }}</th>
                <th class="text-start px-4 py-3 text-xs font-semibold text-ink-500 uppercase tracking-wide">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
            @forelse($rows as $row)
                <tr class="hover:bg-ink-50 transition-colors">
                    {{-- Club cell with avatar bubble — mirrors the users
                         page look exactly (same sizes, same classes). --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                {{ mb_strtoupper(mb_substr($row->club->name_en ?? '?', 0, 2)) }}
                            </div>
                            <span class="font-medium text-ink-900">{{ $row->club->localized('name') }}</span>
                        </div>
                    </td>

                    {{-- Voters + progress bar --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="font-bold tabular-nums text-ink-900">{{ $row->current_voters_count }}</div>
                            @if($row->max_voters)
                                <div class="w-24 h-1.5 rounded-full bg-ink-100 overflow-hidden">
                                    <div class="h-full bg-brand-500"
                                         style="width: {{ min(100, (int) round(($row->current_voters_count / $row->max_voters) * 100)) }}%"></div>
                                </div>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-3 tabular-nums text-ink-500">{{ $row->max_voters ?? '∞' }}</td>

                    {{-- Click-to-copy link chip --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5 max-w-xs">
                            <input type="text" readonly value="{{ $row->publicUrl() }}"
                                   onclick="this.select()"
                                   class="font-mono text-xs rounded-lg border border-ink-200 bg-ink-50 px-2.5 py-1.5 min-w-0 flex-1 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <button type="button"
                                    onclick="navigator.clipboard.writeText('{{ $row->publicUrl() }}'); this.innerText='✓'; setTimeout(()=>this.innerText='📋', 1500);"
                                    title="{{ __('Copy link') }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-ink-200 hover:bg-ink-50 text-xs transition">
                                📋
                            </button>
                            <a href="{{ $row->publicUrl() }}" target="_blank"
                               title="{{ __('Open') }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-ink-200 hover:bg-ink-50 text-xs transition">
                                ↗
                            </a>
                        </div>
                    </td>

                    {{-- Status chip with coloured dot (same pattern as users) --}}
                    <td class="px-4 py-3">
                        @if($row->is_active && ! $row->isFull())
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 text-green-700 px-2.5 py-0.5 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                {{ __('Active') }}
                            </span>
                        @elseif($row->isFull())
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 px-2.5 py-0.5 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                {{ __('Full') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-ink-100 text-ink-500 px-2.5 py-0.5 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-ink-400"></span>
                                {{ __('Disabled') }}
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <form method="post" action="{{ route('admin.campaigns.clubs.update', [$campaign, $row]) }}"
                                  class="flex items-center gap-1">
                                @csrf @method('PATCH')
                                <input type="number" name="max_voters" min="1" value="{{ $row->max_voters }}"
                                       placeholder="∞"
                                       class="w-20 rounded-lg border border-ink-200 px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500"
                                       title="{{ __('Max voters (blank = unlimited)') }}">
                                <input type="hidden" name="is_active" value="{{ $row->is_active ? 1 : 0 }}">
                                <button class="inline-flex items-center gap-1 rounded-lg border border-ink-200 hover:bg-ink-50 px-3 py-1.5 text-xs font-medium text-ink-700 transition"
                                        title="{{ __('Save max voters') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                </button>
                            </form>

                            <form method="post" action="{{ route('admin.campaigns.clubs.regenerate', [$campaign, $row]) }}"
                                  onsubmit="return confirm('{{ __('Generate a new token? The old link will stop working immediately.') }}')">
                                @csrf
                                <button class="inline-flex items-center gap-1 rounded-lg border border-amber-200 hover:bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition">
                                    <span aria-hidden="true">🔄</span>
                                    <span>{{ __('New token') }}</span>
                                </button>
                            </form>

                            <form method="post" action="{{ route('admin.campaigns.clubs.destroy', [$campaign, $row]) }}"
                                  onsubmit="return confirm('{{ __('Remove this club from the campaign?') }}')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-1 rounded-lg border border-rose-200 hover:bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-600 transition"
                                        title="{{ __('Remove') }}">
                                    🗑
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-16 text-center text-ink-400">
                    <div class="text-4xl mb-3">🔗</div>
                    <div class="font-semibold text-ink-700">{{ __('No clubs attached yet') }}</div>
                    <div class="text-sm mt-1">{{ __('Pick clubs above to generate their voting links.') }}</div>
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
