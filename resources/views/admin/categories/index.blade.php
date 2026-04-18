@extends('layouts.admin')

@section('title', __('Categories'))
@section('page_title', $campaign->localized('title'))
@section('page_description', __('Manage categories and candidates'))

@section('content')
<div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="{{ route('admin.campaigns.index') }}" class="hover:underline">{{ __('Campaigns') }}</a>
    <span>·</span>
    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="hover:underline">{{ $campaign->localized('title') }}</a>
    <span>·</span>
    <span>{{ __('Categories') }}</span>
    <span class="ms-auto">
        {{-- TC017 — show the real status here so admins don't think
             "Manage categories" implies the campaign is already active. --}}
        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold
                     {{ match($campaign->status->value) {
                        'draft'            => 'bg-amber-100 text-amber-700',
                        'pending_approval' => 'bg-amber-100 text-amber-800',
                        'rejected'         => 'bg-rose-100 text-rose-700',
                        'published'        => 'bg-blue-100 text-blue-700',
                        'active'           => 'bg-emerald-100 text-emerald-700',
                        default            => 'bg-slate-100 text-slate-700',
                     } }}">
            {{ $campaign->status->label() }}
        </span>
    </span>
</div>

@if(in_array($campaign->status->value, ['draft', 'rejected', 'pending_approval'], true))
    <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 mb-6 flex items-start gap-3 text-sm text-amber-900">
        <span class="text-xl">⚠️</span>
        <div>
            <div class="font-bold">{{ __('Voting is not open yet.') }}</div>
            <div class="mt-0.5">
                @if($campaign->status->value === 'pending_approval')
                    {{ __('Waiting for committee approval — you can still edit categories and candidates meanwhile.') }}
                @else
                    {{ __('Submit the campaign for committee approval from its page before voting can start.') }}
                @endif
            </div>
        </div>
    </div>
@endif

<div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm mb-6">
    <h2 class="text-xl font-bold mb-4">{{ __('Add category') }}</h2>
    <form method="post" action="/admin/campaigns/{{ $campaign->id }}/categories"
          class="grid grid-cols-1 md:grid-cols-6 gap-3">
        @csrf
        <input name="title_ar" placeholder="{{ __('Title AR') }}" required class="rounded-xl border px-3 py-2">
        <input name="title_en" placeholder="{{ __('Title EN') }}" required class="rounded-xl border px-3 py-2">
        <select name="category_type" class="rounded-xl border px-3 py-2">
            @foreach($categoryTypes as $t)
                <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
        </select>
        <select name="position_slot" class="rounded-xl border px-3 py-2">
            <option value="any">{{ __('Any') }}</option>
            <option value="attack">{{ __('Attack') }}</option>
            <option value="midfield">{{ __('Midfield') }}</option>
            <option value="defense">{{ __('Defense') }}</option>
            <option value="goalkeeper">{{ __('Goalkeeper') }}</option>
        </select>
        <div class="flex gap-2">
            <input type="number" name="selection_min" min="1" max="11" value="1" placeholder="min" class="w-20 rounded-xl border px-3 py-2">
            <input type="number" name="selection_max" min="1" max="11" value="1" placeholder="max" class="w-20 rounded-xl border px-3 py-2">
        </div>
        <button class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 font-medium">+ {{ __('Add') }}</button>
    </form>
</div>

@forelse($campaign->categories as $category)
    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm mb-5">
        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h3 class="text-lg font-bold">{{ $category->localized('title') }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $category->category_type?->label() }} ·
                    {{ __('Pick') }} {{ $category->selection_min }}–{{ $category->selection_max }}
                    @if($category->position_slot !== 'any') · {{ __(ucfirst($category->position_slot)) }} @endif
                </p>
            </div>
            <div class="flex gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $category->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $category->is_active ? __('Active') : __('Inactive') }}
                </span>
                <form method="post" action="/admin/categories/{{ $category->id }}" onsubmit="return confirm('{{ __('Delete this category?') }}')">
                    @csrf @method('DELETE')
                    <button class="text-rose-600 hover:underline text-sm">{{ __('Delete') }}</button>
                </form>
            </div>
        </div>

        <form method="post" action="/admin/categories/{{ $category->id }}/candidates"
              class="flex gap-2 mb-4">
            @csrf
            <select name="candidate_type" class="rounded-xl border px-3 py-2">
                <option value="player">{{ __('Player') }}</option>
                <option value="club">{{ __('Club') }}</option>
            </select>
            <select name="candidate_id" class="flex-1 rounded-xl border px-3 py-2">
                <optgroup label="{{ __('Players') }}">
                    @foreach($players as $p)
                        <option value="{{ $p->id }}" data-type="player">{{ $p->localized('name') }} — {{ $p->club?->localized('name') }}</option>
                    @endforeach
                </optgroup>
            </select>
            <button class="rounded-xl bg-slate-900 text-white px-4 py-2 font-medium">+ {{ __('Add candidate') }}</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @forelse($category->candidates as $cand)
                <div class="flex items-center justify-between rounded-xl border border-gray-200 p-3">
                    <div class="text-sm">
                        <div class="font-medium">
                            {{ $cand->player?->localized('name') ?? $cand->club?->localized('name') }}
                        </div>
                        <div class="text-xs text-gray-500">{{ $cand->candidate_type?->value }}</div>
                    </div>
                    <form method="post" action="/admin/candidates/{{ $cand->id }}" onsubmit="return confirm('{{ __('Remove?') }}')">
                        @csrf @method('DELETE')
                        <button class="text-rose-600 hover:underline text-xs">{{ __('Remove') }}</button>
                    </form>
                </div>
            @empty
                <div class="col-span-3 text-center text-gray-400 py-6">{{ __('No candidates yet.') }}</div>
            @endforelse
        </div>
    </div>
@empty
    <div class="rounded-3xl border border-gray-200 bg-white p-12 text-center text-gray-400">
        {{ __('No categories yet. Add one above.') }}
    </div>
@endforelse
@endsection
