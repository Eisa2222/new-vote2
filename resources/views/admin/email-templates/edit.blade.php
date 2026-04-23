@extends('layouts.admin')

@section('title', __('Edit email template'))
@section('page_title', __('Edit email template'))
@section('page_description', $event['label'] ? __($event['label']) : $key)

@section('content')
@php
    $typeLabels = [
        null                   => __('Generic (fallback)'),
        'individual_award'     => __('Individual award'),
        'team_award'           => __('Team award'),
        'team_of_the_season'   => __('Team of the Season'),
    ];
@endphp

<div class="form-wrap">
    <a href="{{ route('admin.email-templates.index') }}" class="btn-ghost mb-4">
        <span aria-hidden="true">←</span>
        <span>{{ __('Back to templates') }}</span>
    </a>

    <div x-data="templateEditor()" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <form method="post" action="{{ route('admin.email-templates.update') }}" class="lg:col-span-2 card space-y-5">
            @csrf
            <input type="hidden" name="key"    value="{{ $key }}">
            <input type="hidden" name="campaign_type" value="{{ $type }}">
            <input type="hidden" name="locale" value="{{ $locale }}">

            <div class="flex items-center gap-2 text-sm text-ink-500 flex-wrap">
                <span class="inline-flex items-center gap-1 rounded-full bg-ink-100 px-3 py-1 text-xs font-semibold">
                    {{ __('Event') }}: <code>{{ $key }}</code>
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 text-brand-700 px-3 py-1 text-xs font-semibold">
                    {{ __('Type') }}: {{ $typeLabels[$type] ?? $type }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-accent-400/20 text-accent-600 px-3 py-1 text-xs font-semibold uppercase">
                    {{ $locale }}
                </span>
            </div>

            <div>
                <label class="field-label">{{ __('Subject') }}</label>
                <input name="subject" value="{{ old('subject', $row->subject) }}" required maxlength="240"
                       x-model="subject" @input="schedulePreview"
                       class="field-input">
            </div>

            <div>
                <label class="field-label">{{ __('Body') }}</label>
                <textarea name="body" rows="12" required x-model="body" @input="schedulePreview"
                          class="field-textarea font-mono text-sm">{{ old('body', $row->body) }}</textarea>
                <p class="field-help">
                    {{ __('Plain text or simple HTML. Use') }}
                    <code class="bg-ink-100 px-1 rounded">{platform.name}</code>, <code class="bg-ink-100 px-1 rounded">{campaign.title}</code>,
                    {{ __('etc. Unknown placeholders are left untouched so typos stay visible.') }}
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="field-checkbox" @checked(old('is_active', $row->is_active))>
                <span>{{ __('Active — send this template for its event') }}</span>
            </label>

            <div class="flex items-center gap-2">
                <button class="btn-save">
                    <span>{{ __('Save template') }}</span>
                </button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="card">
                <h3 class="font-bold mb-3">{{ __('Available variables') }}</h3>
                <p class="text-xs text-ink-500 mb-3">{{ __('Click a variable to copy it to your clipboard.') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($vars as $v)
                        <button type="button" @click="copyVar('{{ '{'.$v.'}' }}')"
                                class="inline-flex items-center gap-1 rounded-lg bg-ink-100 hover:bg-brand-100 hover:text-brand-700 text-ink-700 px-2.5 py-1 text-xs font-mono transition">
                            {{ '{'.$v.'}' }}
                        </button>
                    @endforeach
                </div>
                <div x-show="copied" x-cloak class="mt-2 text-xs text-brand-700">✓ <span x-text="copied"></span> {{ __('copied') }}</div>
            </div>

            <div class="card">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold">{{ __('Live preview') }}</h3>
                    <span x-show="previewing" x-cloak class="text-xs text-ink-500">⟳ {{ __('rendering...') }}</span>
                </div>
                <div class="rounded-xl border border-ink-200 p-3 bg-ink-50/40">
                    <div class="text-[11px] text-ink-500 uppercase tracking-wider">{{ __('Subject') }}</div>
                    <div class="font-semibold mb-3" x-text="preview.subject || '—'"></div>
                    <div class="text-[11px] text-ink-500 uppercase tracking-wider">{{ __('Body') }}</div>
                    <div class="text-sm whitespace-pre-wrap" x-html="preview.body_safe || '—'"></div>
                </div>
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script>
function templateEditor() {
    return {
        subject: @js(old('subject', $row->subject)),
        body:    @js(old('body', $row->body)),
        preview: { subject: '', body_safe: '' },
        previewing: false,
        copied: '',
        _timer: null,
        schedulePreview() {
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this.fetchPreview(), 300);
        },
        async fetchPreview() {
            this.previewing = true;
            try {
                const res = await fetch(@js(route('admin.email-templates.preview')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ subject: this.subject, body: this.body }),
                });
                const data = await res.json();
                this.preview.subject  = data.subject || '';
                // Escape then restore line breaks so HTML doesn't render
                // raw tags from untrusted template bodies inside the preview.
                const esc = (data.body || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                this.preview.body_safe = esc;
            } finally {
                this.previewing = false;
            }
        },
        copyVar(v) {
            navigator.clipboard?.writeText(v);
            this.copied = v;
            setTimeout(() => this.copied = '', 1500);
        },
        init() { this.fetchPreview(); },
    };
}
</script>
@endpush
@endsection
