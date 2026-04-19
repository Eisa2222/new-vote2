@extends('layouts.admin')

@section('title', __('Email templates'))
@section('page_title', __('Email templates'))
@section('page_description', __('Edit the subject and body of every system email. Each template can be customised per award type and per language.'))

@section('content')
@php
    $typeLabels = [
        null                   => __('Generic (fallback)'),
        'individual_award'     => __('Individual award'),
        'team_award'           => __('Team award'),
        'team_of_the_season'   => __('Team of the Season'),
    ];
@endphp

<div class="form-wrap space-y-6">
    @foreach($events as $eventKey => $event)
        <div class="card">
            <div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
                <div>
                    <h2 class="text-lg font-bold">{{ __($event['label']) }}</h2>
                    <p class="text-sm text-ink-500 mt-1">
                        <code class="font-mono text-xs bg-ink-100 px-2 py-0.5 rounded">{{ $eventKey }}</code>
                        @if($event['per_type'])
                            <span class="ms-2 inline-flex items-center gap-1 rounded-full bg-brand-50 text-brand-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider">
                                {{ __('Per award type') }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto -mx-2">
                <table data-datatable-scope class="w-full text-sm">
                    <thead class="text-ink-500 border-b border-ink-200">
                        <tr>
                            <th class="text-start py-2 px-2">{{ __('Award type') }}</th>
                            @foreach($locales as $lc)
                                <th class="text-center py-2 px-2 uppercase text-xs tracking-wider">{{ $lc }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach($types as $type)
                            @continue(! $event['per_type'] && $type !== null)
                            <tr class="hover:bg-ink-50">
                                <td class="py-2 px-2 font-medium">
                                    {{ $typeLabels[$type] ?? $type }}
                                </td>
                                @foreach($locales as $lc)
                                    @php
                                        $cellKey = $eventKey.'|'.($type ?? '').'|'.$lc;
                                        $row = $existing[$cellKey] ?? null;
                                    @endphp
                                    <td class="py-2 px-2 text-center">
                                        <a href="{{ route('admin.email-templates.edit', ['key' => $eventKey, 'type' => $type, 'locale' => $lc]) }}"
                                           class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs font-medium transition
                                                  {{ $row
                                                    ? ($row->is_active
                                                        ? 'border-brand-500/50 text-brand-700 hover:bg-brand-50 bg-brand-50/40'
                                                        : 'border-ink-200 text-ink-500 hover:bg-ink-50')
                                                    : 'border-dashed border-ink-300 text-ink-500 hover:border-brand-400 hover:text-brand-700' }}">
                                            @if($row)
                                                {{ $row->is_active ? '✓ '.__('Custom') : '⏸ '.__('Disabled') }}
                                            @else
                                                ✎ {{ __('Create') }}
                                            @endif
                                        </a>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="rounded-2xl bg-info-500/5 border border-info-500/30 text-info-500 p-4 text-sm">
        💡 {{ __('Per-award-type templates override the generic fallback. Use them to write a different announcement for Team of the Season vs Player of the Year, for example.') }}
    </div>
</div>
@endsection
