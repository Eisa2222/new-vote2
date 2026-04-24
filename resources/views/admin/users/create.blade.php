@extends('layouts.admin')
@section('content')
    <h1 class="text-2xl font-bold mb-6 text-slate-800">{{ __('New User') }}</h1>

    <form method="post" action="{{ route('admin.users.store') }}"
        class="bg-white rounded-2xl shadow p-6 md:p-8 space-y-5 form-wrap">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Name') }}</label>
            <input name="name" value="{{ old('name', $user->name) }}" required class="w-full border rounded-lg px-3 py-2">
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                class="w-full border rounded-lg px-3 py-2">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                {{ __('Password') }}
                <span class="text-slate-400 text-xs">({{ __('leave empty to send an invitation email') }})</span>
            </label>
            <div class="relative">
                <input type="password" name="password" id="passwordInput" class="w-full border rounded-lg px-3 py-2 pr-10"
                    autocomplete="new-password" placeholder="{{ __('Leave blank — we will email them a setup link') }}"
                    x-on:input="checkPassword($event.target.value)">

                {{-- Toggle visibility button --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                    x-on:click="showPassword = !showPassword" x-init="$watch('showPassword', v => document.getElementById('passwordInput').type = v ? 'text' : 'password')" x-data="{ showPassword: false }"
                    title="{{ __('Toggle password visibility') }}">
                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>

            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror

            {{-- Password criteria (shown only when typing) --}}
            <div x-data="{
                password: '',
                get len() { return this.password.length >= 8 },
                get upper() { return /[A-Z]/.test(this.password) },
                get lower() { return /[a-z]/.test(this.password) },
                get num() { return /[0-9]/.test(this.password) },
                get special() { return /[^A-Za-z0-9]/.test(this.password) },
                checkPassword(val) { this.password = val }
            }"
                x-on:input.window="checkPassword($event.target.name === 'password' ? $event.target.value : password)">
                <div x-show="password.length > 0" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-2 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-1.5">
                    <p class="font-medium text-slate-600 mb-2">{{ __('Password requirements') }}</p>
                    <ul class="space-y-1">
                        <li class="flex items-center gap-2" :class="len ? 'text-green-600' : 'text-slate-400'">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full transition-colors"
                                :class="len ? 'bg-green-500' : 'bg-slate-200'">
                                <svg class="w-2.5 h-2.5 stroke-white fill-none" viewBox="0 0 10 8" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="1,4 3.5,7 9,1" />
                                </svg>
                            </span>
                            {{ __('At least 8 characters') }}
                        </li>
                        <li class="flex items-center gap-2" :class="upper ? 'text-green-600' : 'text-slate-400'">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full transition-colors"
                                :class="upper ? 'bg-green-500' : 'bg-slate-200'">
                                <svg class="w-2.5 h-2.5 stroke-white fill-none" viewBox="0 0 10 8" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="1,4 3.5,7 9,1" />
                                </svg>
                            </span>
                            {{ __('One uppercase letter (A-Z)') }}
                        </li>
                        <li class="flex items-center gap-2" :class="lower ? 'text-green-600' : 'text-slate-400'">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full transition-colors"
                                :class="lower ? 'bg-green-500' : 'bg-slate-200'">
                                <svg class="w-2.5 h-2.5 stroke-white fill-none" viewBox="0 0 10 8" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="1,4 3.5,7 9,1" />
                                </svg>
                            </span>
                            {{ __('One lowercase letter (a-z)') }}
                        </li>
                        <li class="flex items-center gap-2" :class="num ? 'text-green-600' : 'text-slate-400'">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full transition-colors"
                                :class="num ? 'bg-green-500' : 'bg-slate-200'">
                                <svg class="w-2.5 h-2.5 stroke-white fill-none" viewBox="0 0 10 8" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="1,4 3.5,7 9,1" />
                                </svg>
                            </span>
                            {{ __('One number (0-9)') }}
                        </li>
                        <li class="flex items-center gap-2" :class="special ? 'text-green-600' : 'text-slate-400'">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full transition-colors"
                                :class="special ? 'bg-green-500' : 'bg-slate-200'">
                                <svg class="w-2.5 h-2.5 stroke-white fill-none" viewBox="0 0 10 8" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="1,4 3.5,7 9,1" />
                                </svg>
                            </span>
                            {{ __('One special character (!@#$%...)') }}
                        </li>
                    </ul>
                </div>
            </div>

            <p class="mt-1.5 text-xs text-ink-500">
                {{ __('If you leave this blank, the user gets an invitation email with a one-time link to choose their own password.') }}
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('Roles') }} <span class="text-rose-600">*</span>
                <span class="text-slate-400 text-xs font-normal">({{ __('at least one required') }})</span>
            </label>
            @error('roles')
                <p class="text-rose-600 text-sm mb-2">{{ $message }}</p>
            @enderror
            @php
                $roleLabels = [
                    'super_admin' => __('Super Admin'),
                    'committee' => __('Voting Committee'),
                    'campaign_manager' => __('Campaign Manager'),
                    'auditor' => __('Auditor'),
                ];
                $roleHints = [
                    'super_admin' => __('Full access to every section.'),
                    'committee' => __('Sees campaigns and approves results only.'),
                    'campaign_manager' => __('Manages clubs, players and creates campaigns.'),
                    'auditor' => __('Read-only observer.'),
                ];
            @endphp
            <div class="space-y-2">
                @foreach ($roles as $r)
                    <label
                        class="flex items-start gap-3 rounded-xl border border-ink-200 p-3 cursor-pointer hover:bg-ink-50">
                        <input type="checkbox" name="roles[]" value="{{ $r->name }}" class="mt-1"
                            @checked(in_array($r->name, old('roles', $user->roles->pluck('name')->all())))>
                        <span>
                            <span class="font-semibold">{{ $roleLabels[$r->name] ?? $r->name }}</span>
                            @if (!empty($roleHints[$r->name]))
                                <span class="block text-xs text-ink-500 mt-0.5">{{ $roleHints[$r->name] }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </div>


        <div class="sticky bottom-0 bg-white pt-4 border-t border-ink-200 flex gap-3 items-center justify-end"> <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                <span>{{ __('Save') }}</span>
            </button>
            <a href="{{ route('admin.users.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-ink-200 hover:bg-ink-50 text-ink-700 px-5 py-2.5 text-sm font-medium transition">
                {{ __('Cancel') }}
            </a>

        </div>

    </form>
@endsection
