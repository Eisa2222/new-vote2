@extends('layouts.admin')

@section('title', __('Clubs'))
@section('page_title', __('Clubs'))
@section('page_description', __('View and manage all participating clubs'))

@section('content')
@include('admin._partials.import-export-bar', [
    'exportUrl'   => '/admin/clubs/export',
    'templateUrl' => '/admin/clubs/export/template',
    'importUrl'   => '/admin/clubs/import',
    'label'       => __('clubs'),
])

<div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm space-y-5 mt-4">
    <form method="get" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex flex-col md:flex-row gap-3 w-full lg:max-w-3xl">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search by club name') }}..."
                   class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            <select name="status" class="rounded-2xl border border-gray-300 px-4 py-3 min-w-[180px]">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active"   @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </select>
            <button class="btn-ghost">{{ __('Filter') }}</button>
        </div>
        <a href="{{ route('admin.clubs.create') }}" class="btn-save">
            <span>+</span>
            <span>{{ __('New Club') }}</span>
        </a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-gray-500">
                    <th class="text-start py-3">{{ __('Logo') }}</th>
                    <th class="text-start py-3">{{ __('Name') }}</th>
                    <th class="text-start py-3">{{ __('Short') }}</th>
                    <th class="text-start py-3">{{ __('Sports') }}</th>
                    <th class="text-start py-3">{{ __('Status') }}</th>
                    <th class="text-start py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clubs as $club)
                    <tr class="border-b last:border-0 hover:bg-slate-50">
                        <td class="py-4">
                            @if($club->logo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($club->logo_path) }}"
                                     class="w-12 h-12 rounded-2xl object-cover" alt="">
                            @else
                                <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center text-xl">🏟️</div>
                            @endif
                        </td>
                        <td class="py-4 font-medium">{{ $club->localized('name') }}</td>
                        <td class="py-4 text-gray-500">{{ $club->short_name }}</td>
                        <td class="py-4 text-gray-600">{{ $club->sports->map(fn($s) => $s->localized('name'))->join('، ') }}</td>
                        <td class="py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $club->status->value === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $club->status->label() }}
                            </span>
                        </td>
                        <td class="py-4">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="/admin/clubs/{{ $club->id }}/edit"
                                   class="rounded-lg border border-ink-200 hover:bg-ink-50 px-3 py-1.5 text-xs font-medium">
                                    ✏️ {{ __('Edit') }}
                                </a>
                                <form method="post" action="/admin/clubs/{{ $club->id }}/toggle">
                                    @csrf
                                    <button class="rounded-lg border border-warning-500/50 text-warning-500 hover:bg-warning-500/10 px-3 py-1.5 text-xs font-medium">
                                        @if($club->status->value === 'active')
                                            ⏸ {{ __('Disable') }}
                                        @else
                                            ▶ {{ __('Enable') }}
                                        @endif
                                    </button>
                                </form>
                                <form method="post" action="/admin/clubs/{{ $club->id }}"
                                      onsubmit="return confirm('{{ __('Delete this club?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-danger-500/50 text-danger-600 hover:bg-danger-500/10 px-3 py-1.5 text-xs font-medium">
                                        🗑 {{ __('Delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-16 text-center text-gray-400">
                        {{ __('No clubs yet. Create your first club.') }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $clubs->links() }}</div>
</div>
@endsection
