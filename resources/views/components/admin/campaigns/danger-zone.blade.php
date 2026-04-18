@props(['campaign'])

@can('campaigns.delete', $campaign)
    <div class="mt-8 rounded-3xl border-2 border-rose-200 bg-rose-50 p-6">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h3 class="font-bold text-rose-900">🗑️ {{ __('Danger zone') }}</h3>
                <p class="text-sm text-rose-800 mt-1">
                    {{ __('Deleting removes the campaign, its categories, candidates, votes and result. This cannot be undone.') }}
                </p>
            </div>
            <form method="post" action="{{ route('admin.campaigns.destroy', $campaign) }}"
                  onsubmit="return confirm('{{ __('Permanently delete :t? This cannot be undone.', ['t' => $campaign->localized('title')]) }}')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="rounded-2xl bg-rose-600 hover:bg-rose-700 text-white px-6 py-3 font-bold shadow">
                    🗑️ {{ __('Delete campaign') }}
                </button>
            </form>
        </div>
    </div>
@endcan
