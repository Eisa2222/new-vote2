@props(['campaign'])

<div class="bg-white rounded-2xl shadow p-6">
    <h2 class="font-semibold text-slate-800 mb-4">{{ __('Categories') }}</h2>

    @foreach($campaign->categories as $category)
        <div class="border rounded-lg p-4 mb-3">
            <div class="flex justify-between items-center">
                <h3 class="font-medium">{{ $category->localized('title') }}</h3>
                <span class="text-xs text-slate-500">
                    {{ __('Pick exactly :n', ['n' => $category->required_picks]) }}
                    @if($category->position_slot !== 'any')
                        · {{ __(ucfirst($category->position_slot)) }}
                    @endif
                </span>
            </div>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                @foreach($category->candidates as $candidate)
                    @php
                        $label = $candidate->player?->localized('name')
                              ?? $candidate->club?->localized('name');
                        $subLabel = $candidate->player?->club?->localized('name');
                    @endphp
                    <div class="text-sm text-slate-600 border rounded p-2">
                        {{ $label }}
                        @if($subLabel)
                            <span class="text-xs text-slate-400">({{ $subLabel }})</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
