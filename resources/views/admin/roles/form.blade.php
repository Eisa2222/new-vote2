@extends('layouts.admin')

@php
    $isEditing = $role->exists;
    $action    = $isEditing
        ? route('admin.roles.update', $role)
        : route('admin.roles.store');

    $groupLabels = [
        'clubs'     => __('Clubs'),
        'players'   => __('Players'),
        'campaigns' => __('Campaigns'),
        'results'   => __('Results'),
        'users'     => __('Users'),
    ];

    // Translation keys live under `abilities.*` (not `permissions.*`)
    // to avoid a case-insensitive filename collision on Windows with
    // the JSON key "Permissions".
    $permissionLabels = [];
    foreach ($permissionTree as $permissionGroup) {
        foreach ($permissionGroup as $permission) {
            $resolved = __('abilities.'.$permission->name);
            $permissionLabels[$permission->name] = is_string($resolved) ? $resolved : '';
        }
    }
@endphp

@section('title', $isEditing ? __('Edit role') : __('New role'))
@section('page_title', $isEditing ? __('Edit role') : __('New role'))
@section('page_description', __('Pick exactly which actions this role is allowed to perform.'))

@section('content')
<form method="post" action="{{ $action }}" class="space-y-5">
    @csrf
    @if($isEditing) @method('PUT') @endif

    <div class="rounded-3xl bg-white border border-ink-200 shadow-sm p-5 md:p-6">
        <h2 class="text-lg font-bold text-ink-900 mb-4">{{ __('Role identity') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-ink-800 mb-1">{{ __('Role key (internal)') }}</label>
                @if($isEditing)
                    <input value="{{ $role->name }}" disabled
                           class="w-full rounded-xl border border-ink-200 px-3 py-2.5 font-mono text-sm bg-ink-50 text-ink-500">
                    <p class="text-xs text-ink-500 mt-1">{{ __('The role key cannot be changed after creation.') }}</p>
                @else
                    <input name="name" value="{{ old('name') }}" required
                           pattern="[a-z0-9_]+"
                           class="w-full rounded-xl border border-ink-200 px-3 py-2.5 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                           placeholder="role_key">
                    <p class="text-xs text-ink-500 mt-1">{{ __('Lowercase letters, digits and underscores only.') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-3xl bg-white border border-ink-200 shadow-sm p-5 md:p-6">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
            <h2 class="text-lg font-bold text-ink-900">{{ __('Permissions') }}</h2>
            <div class="flex gap-2">
                <button type="button"
                        onclick="document.querySelectorAll('[name=&quot;permissions[]&quot;]').forEach(c => c.checked = true)"
                        class="rounded-xl border border-ink-200 hover:bg-ink-50 px-3 py-1.5 text-xs font-semibold">
                    ☑ {{ __('Select all') }}
                </button>
                <button type="button"
                        onclick="document.querySelectorAll('[name=&quot;permissions[]&quot;]').forEach(c => c.checked = false)"
                        class="rounded-xl border border-ink-200 hover:bg-ink-50 px-3 py-1.5 text-xs font-semibold">
                    ☐ {{ __('Clear all') }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($permissionTree as $group => $permissions)
                @php $groupLabel = $groupLabels[$group] ?? ucfirst((string) $group); @endphp
                <div class="rounded-2xl border border-ink-200 overflow-hidden">
                    <header class="bg-gradient-to-r from-brand-600 to-brand-700 text-white px-4 py-2.5 flex items-center justify-between">
                        <span class="font-bold text-sm">{{ $groupLabel }}</span>
                        <button type="button" data-group-toggle="{{ $group }}"
                                class="text-[10px] bg-white/15 hover:bg-white/25 rounded-full px-2 py-1 font-semibold">
                            {{ __('Toggle') }}
                        </button>
                    </header>
                    <div class="p-3 space-y-1.5">
                        @foreach($permissions as $permission)
                            @php
                                $checked     = in_array($permission->name, old('permissions', $selected), true);
                                $description = $permissionLabels[$permission->name] ?? '';
                            @endphp
                            <label class="flex items-start gap-2 rounded-lg p-2 hover:bg-ink-50 cursor-pointer has-[:checked]:bg-brand-50 has-[:checked]:border-brand-300 border border-transparent transition">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                       data-group="{{ $group }}"
                                       class="mt-0.5 w-4 h-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                                       @checked($checked)>
                                <span class="flex-1 min-w-0">
                                    <span class="font-mono text-xs text-ink-800">{{ $permission->name }}</span>
                                    @if($description !== '')
                                        <span class="block text-[11px] text-ink-500 mt-0.5">{{ $description }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="sticky bottom-0 bg-gradient-to-t from-white via-white/95 to-transparent pt-4 pb-4 z-10">
        <div class="rounded-2xl bg-white border border-ink-200 shadow-lg p-4 flex items-center justify-between gap-3">
            <a href="{{ route('admin.roles.index') }}"
               class="rounded-xl border border-ink-200 hover:bg-ink-50 text-ink-700 px-5 py-2.5 font-semibold">
                {{ __('Cancel') }}
            </a>
            <button type="submit"
                    class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-8 py-2.5 font-bold shadow-brand">
                {{ $isEditing ? __('Save changes') : __('Create role') }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    document.querySelectorAll('[data-group-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.dataset.groupToggle;
            const boxes = document.querySelectorAll(`[name="permissions[]"][data-group="${group}"]`);
            const allChecked = [...boxes].every(b => b.checked);
            boxes.forEach(b => b.checked = !allChecked);
        });
    });
</script>
@endpush
@endsection
