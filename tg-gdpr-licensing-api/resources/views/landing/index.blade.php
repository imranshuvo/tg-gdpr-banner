<!DOCTYPE html>
@php
    $appName   = config('app.name');
    $supported = config('locales.supported', []);
    $current   = app()->getLocale();
@endphp
<html lang="{{ str_replace('_', '-', $current) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} — {{ __('landing.hero.headline_a') }} {{ __('landing.hero.headline_b') }}</title>
    <meta name="description" content="{{ __('landing.hero.lede', ['app' => $appName]) }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .bg-grid {
            background-image:
                linear-gradient(to right, rgba(99,102,241,0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(99,102,241,0.08) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse at top, rgba(0,0,0,0.7), transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at top, rgba(0,0,0,0.7), transparent 70%);
        }
        .hero-glow::before {
            content: '';
            position: absolute;
            inset: -10%;
            background: radial-gradient(50% 40% at 50% 0%, rgba(99,102,241,0.18), transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        details[open] summary svg.chev { transform: rotate(180deg); }
        summary::-webkit-details-marker { display: none; }
    </style>
</head>
<body class="antialiased text-slate-900 bg-white">

    {{-- ============== NAV ============== --}}
    <header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/80 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-sm shadow-indigo-600/30">
                    <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5h.01"/><path d="M15.5 11.5h.01"/><path d="M11 14.5h.01"/></svg>
                </span>
                <span class="text-lg font-bold tracking-tight text-slate-900">{{ $appName }}</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#features" class="hover:text-slate-900 transition">{{ __('landing.nav.features') }}</a>
                <a href="#showcase" class="hover:text-slate-900 transition">{{ __('landing.nav.showcase') }}</a>
                <a href="#pricing" class="hover:text-slate-900 transition">{{ __('landing.nav.pricing') }}</a>
                <a href="#faq" class="hover:text-slate-900 transition">{{ __('landing.nav.faq') }}</a>
            </nav>

            <div class="flex items-center gap-2">
                {{-- Locale switcher --}}
                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm font-medium text-slate-600 hover:border-slate-300 hover:text-slate-900 transition"
                            :aria-expanded="open" aria-haspopup="menu" aria-label="{{ __('landing.nav.language') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="12" cy="12" r="10"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/></svg>
                        <span class="uppercase tracking-wide text-xs font-semibold">{{ $current }}</span>
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-slate-400"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak x-transition.opacity
                         class="absolute right-0 mt-2 w-44 rounded-xl border border-slate-200 bg-white shadow-lg shadow-slate-900/5 py-1.5 z-50" role="menu">
                        @foreach($supported as $code => $info)
                            <a href="{{ route('locale.switch', ['locale' => $code]) }}"
                               class="flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-slate-50 {{ $code === $current ? 'text-indigo-600 font-semibold' : 'text-slate-700' }}"
                               role="menuitem">
                                <span class="text-base leading-none">{{ $info['flag'] }}</span>
                                <span>{{ $info['native'] }}</span>
                                @if($code === $current)
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="ml-auto w-4 h-4 text-indigo-600"><path fill-rule="evenodd" d="M16.704 5.296a1 1 0 0 1 0 1.408l-8 8a1 1 0 0 1-1.408 0l-4-4a1 1 0 0 1 1.408-1.408L8 12.59l7.296-7.294a1 1 0 0 1 1.408 0Z" clip-rule="evenodd"/></svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                @auth
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard') }}"
                       class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        {{ __('landing.nav.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex text-sm font-medium text-slate-600 hover:text-slate-900 px-3 py-2 transition">{{ __('landing.nav.sign_in') }}</a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition shadow-sm">
                        {{ __('landing.nav.start_free') }}
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- ============== HERO ============== --}}
    <section class="relative overflow-hidden hero-glow">
        <div class="absolute inset-0 bg-grid"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 lg:pt-28 lg:pb-32">
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                <div class="lg:col-span-6">
                    <a href="#features" class="inline-flex items-center gap-2 rounded-full border border-indigo-200/70 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        {{ __('landing.hero.pill') }}
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                    </a>

                    <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.05]">
                        {{ __('landing.hero.headline_a') }}<br class="hidden sm:block">
                        <span class="bg-gradient-to-r from-indigo-600 via-violet-600 to-sky-500 bg-clip-text text-transparent">{{ __('landing.hero.headline_b') }}</span>
                    </h1>

                    <p class="mt-5 text-lg text-slate-600 max-w-xl leading-relaxed">
                        {{ __('landing.hero.lede', ['app' => $appName]) }}
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition shadow-lg shadow-slate-900/10">
                            {{ __('landing.hero.cta_primary') }}
                            <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                        </a>
                        <a href="#showcase"
                           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition">
                            <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-slate-400"><path d="M6.3 2.84A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.27l9.344-5.89a1.5 1.5 0 0 0 0-2.54L6.3 2.84Z"/></svg>
                            {{ __('landing.hero.cta_secondary') }}
                        </a>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-600">
                        @foreach(['trial', 'live_5', 'cancel_anytime'] as $k)
                            <div class="flex items-center gap-2">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-emerald-500"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                {{ __('landing.hero.'.$k) }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Hero mock --}}
                <div class="lg:col-span-6 relative">
                    <div class="absolute -inset-4 bg-gradient-to-tr from-indigo-200/40 via-violet-200/30 to-sky-200/40 blur-2xl rounded-[28px]" aria-hidden="true"></div>

                    <div class="relative rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-indigo-900/10 overflow-hidden">
                        {{-- Browser chrome --}}
                        <div class="flex items-center gap-2 px-4 h-10 border-b border-slate-200 bg-slate-50">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <div class="ml-3 flex-1 max-w-xs h-5 rounded-md bg-white border border-slate-200 flex items-center px-2 gap-1.5 text-[10px] text-slate-500">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-emerald-500"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/></svg>
                                yourwordpress.site
                            </div>
                        </div>

                        {{-- Faux page preview --}}
                        <div class="relative h-[360px] bg-gradient-to-b from-slate-50 to-white">
                            <div class="px-6 pt-6 space-y-3">
                                <div class="h-3 w-24 rounded bg-slate-200"></div>
                                <div class="h-6 w-3/4 rounded bg-slate-300"></div>
                                <div class="h-3 w-2/3 rounded bg-slate-200"></div>
                                <div class="grid grid-cols-3 gap-3 pt-3">
                                    <div class="h-20 rounded-lg bg-gradient-to-br from-indigo-100 to-violet-100"></div>
                                    <div class="h-20 rounded-lg bg-slate-100"></div>
                                    <div class="h-20 rounded-lg bg-slate-100"></div>
                                </div>
                            </div>

                            {{-- Banner preview --}}
                            <div class="absolute left-4 right-4 bottom-4 rounded-xl bg-white border border-slate-200 shadow-xl shadow-slate-900/10 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 inline-flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600">
                                        <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5h.01"/><path d="M15.5 11.5h.01"/><path d="M11 14.5h.01"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-slate-900">{{ __('landing.hero.banner_title') }}</div>
                                        <p class="mt-0.5 text-xs text-slate-500 leading-relaxed">{{ __('landing.hero.banner_body') }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2 justify-end">
                                    <button class="text-xs font-semibold rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100 transition">{{ __('landing.hero.banner_reject') }}</button>
                                    <button class="text-xs font-semibold rounded-md px-3 py-1.5 text-slate-700 border border-slate-200 hover:bg-slate-50 transition">{{ __('landing.hero.banner_prefs') }}</button>
                                    <button class="text-xs font-semibold rounded-md px-3 py-1.5 text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">{{ __('landing.hero.banner_accept') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Floating compliance chips --}}
                    <div class="absolute -left-4 top-12 hidden md:flex items-center gap-2 rounded-full bg-white border border-slate-200 shadow-lg shadow-slate-900/5 px-3 py-1.5">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-emerald-500"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                        <span class="text-xs font-semibold text-slate-700">{{ __('landing.hero.chip_gdpr') }}</span>
                    </div>
                    <div class="absolute -right-4 bottom-24 hidden md:flex items-center gap-2 rounded-full bg-white border border-slate-200 shadow-lg shadow-slate-900/5 px-3 py-1.5">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-indigo-600"><path d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"/></svg>
                        <span class="text-xs font-semibold text-slate-700">{{ __('landing.hero.chip_uptime') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============== TRUST STRIP ============== --}}
    <section class="border-y border-slate-200 bg-slate-50/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <p class="text-center text-xs uppercase tracking-[0.2em] font-semibold text-slate-500">{{ __('landing.trust.eyebrow') }}</p>
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 items-center">
                @php
                    // IAB TCF v2.2 is NOT in the badge list yet — schema exists but
                    // TC string generation is post-launch. Don't claim it until it ships.
                    $badges = [
                        ['GDPR', 'EU 2016/679'],
                        ['ePrivacy', 'Directive 2002/58'],
                        ['CCPA / CPRA', 'California'],
                        ['LGPD', 'Brazil'],
                        ['Consent Mode v2', 'Google'],
                        ['DSAR workflow', 'Article 15–22'],
                    ];
                @endphp
                @foreach($badges as $badge)
                    <div class="flex flex-col items-center text-center">
                        <span class="text-sm font-bold text-slate-700 tracking-tight">{{ $badge[0] }}</span>
                        <span class="text-[11px] text-slate-500 mt-0.5">{{ $badge[1] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============== FEATURES ============== --}}
    <section id="features" class="py-24 lg:py-28">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center">
                <span class="inline-block text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">{{ __('landing.features.eyebrow') }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight text-slate-900">{{ __('landing.features.h2') }}</h2>
                <p class="mt-4 text-lg text-slate-600">{{ __('landing.features.sub') }}</p>
            </div>

            <div class="mt-16 grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $features = [
                        ['gdpr',      '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>'],
                        ['gcm',       '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/>'],
                        ['scan',      '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>'],
                        ['custom',    '<path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/>'],
                        ['analytics', '<path d="M3 3v18h18"/><path d="m7 14 4-4 4 4 5-5"/>'],
                        ['dsar',      '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/>'],
                    ];
                @endphp

                @foreach($features as [$slug, $icon])
                    <div class="group relative rounded-2xl border border-slate-200 bg-white p-6 hover:border-slate-300 hover:shadow-lg hover:shadow-slate-900/5 transition">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 border border-indigo-100 text-indigo-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">{!! $icon !!}</svg>
                        </div>
                        <h3 class="mt-5 text-base font-semibold text-slate-900">{{ __('landing.features.'.$slug.'_t') }}</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ __('landing.features.'.$slug.'_b') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============== BANNER SHOWCASE ============== --}}
    <section id="showcase" class="py-24 lg:py-28 bg-gradient-to-b from-slate-50 to-white border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center">
                <span class="inline-block text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">{{ __('landing.showcase.eyebrow') }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight text-slate-900">{{ __('landing.showcase.h2') }}</h2>
                <p class="mt-4 text-lg text-slate-600">{{ __('landing.showcase.sub') }}</p>
            </div>

            <div class="mt-14 grid lg:grid-cols-3 gap-6">
                {{-- Bottom bar --}}
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="aspect-[4/3] rounded-xl bg-gradient-to-br from-slate-100 to-slate-50 border border-slate-200 relative overflow-hidden">
                        <div class="absolute inset-x-3 top-3 space-y-1.5">
                            <div class="h-2 w-1/2 rounded bg-slate-200"></div>
                            <div class="h-2 w-1/3 rounded bg-slate-200"></div>
                        </div>
                        <div class="absolute inset-x-3 bottom-3 rounded-lg bg-white border border-slate-200 shadow p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-[10px] font-semibold text-slate-700 truncate">{{ __('landing.showcase.preview_short') }}</div>
                                <div class="flex gap-1 flex-none">
                                    <span class="text-[9px] font-semibold rounded px-1.5 py-0.5 bg-slate-100 text-slate-600">{{ __('landing.showcase.reject') }}</span>
                                    <span class="text-[9px] font-semibold rounded px-1.5 py-0.5 bg-indigo-600 text-white">{{ __('landing.showcase.accept') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('landing.showcase.bottom_t') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ __('landing.showcase.bottom_b') }}</p>
                </article>

                {{-- Center modal --}}
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="aspect-[4/3] rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 border border-slate-200 relative overflow-hidden">
                        <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-[2px]"></div>
                        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[78%] rounded-lg bg-white border border-slate-200 shadow-lg p-3">
                            <div class="text-[11px] font-bold text-slate-900">{{ __('landing.showcase.preview_modal_title') }}</div>
                            <div class="mt-1 text-[9px] text-slate-500 leading-snug">{{ __('landing.showcase.preview_modal_body') }}</div>
                            <div class="mt-2 space-y-1">
                                <div class="flex items-center justify-between"><span class="text-[9px] text-slate-700">{{ __('landing.showcase.necessary') }}</span><span class="w-5 h-2.5 rounded-full bg-emerald-500"></span></div>
                                <div class="flex items-center justify-between"><span class="text-[9px] text-slate-700">{{ __('landing.showcase.analytics') }}</span><span class="w-5 h-2.5 rounded-full bg-slate-300"></span></div>
                            </div>
                            <div class="mt-2 flex justify-end"><span class="text-[9px] font-semibold rounded px-1.5 py-0.5 bg-indigo-600 text-white">{{ __('landing.showcase.save') }}</span></div>
                        </div>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('landing.showcase.modal_t') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ __('landing.showcase.modal_b') }}</p>
                </article>

                {{-- Corner box --}}
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="aspect-[4/3] rounded-xl bg-gradient-to-br from-slate-100 to-slate-50 border border-slate-200 relative overflow-hidden">
                        <div class="absolute inset-x-3 top-3 space-y-1.5">
                            <div class="h-2 w-2/3 rounded bg-slate-200"></div>
                            <div class="h-2 w-1/2 rounded bg-slate-200"></div>
                        </div>
                        <div class="absolute right-3 bottom-3 w-[58%] rounded-lg bg-white border border-slate-200 shadow p-2.5">
                            <div class="text-[10px] font-bold text-slate-900">{{ __('landing.showcase.preview_corner_title') }}</div>
                            <div class="mt-0.5 text-[9px] text-slate-500 leading-snug">{{ __('landing.showcase.preview_short2') }}</div>
                            <div class="mt-1.5 flex justify-end gap-1">
                                <span class="text-[9px] font-semibold rounded px-1.5 py-0.5 bg-slate-100 text-slate-600">{{ __('landing.showcase.reject') }}</span>
                                <span class="text-[9px] font-semibold rounded px-1.5 py-0.5 bg-indigo-600 text-white">{{ __('landing.showcase.accept') }}</span>
                            </div>
                        </div>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('landing.showcase.corner_t') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ __('landing.showcase.corner_b') }}</p>
                </article>
            </div>
        </div>
    </section>

    {{-- ============== HOW IT WORKS ============== --}}
    <section id="how-it-works" class="py-24 lg:py-28">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center">
                <span class="inline-block text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">{{ __('landing.steps.eyebrow') }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight text-slate-900">{{ __('landing.steps.h2') }}</h2>
            </div>

            <div class="mt-16 grid md:grid-cols-3 gap-6 relative">
                <div class="hidden md:block absolute top-7 left-[16%] right-[16%] h-px bg-gradient-to-r from-transparent via-indigo-200 to-transparent"></div>

                @foreach(['s1', 's2', 's3'] as $i => $key)
                    <article class="relative rounded-2xl border border-slate-200 bg-white p-6">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white text-lg font-bold shadow-md shadow-indigo-600/30">
                            {{ $i + 1 }}
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ __('landing.steps.'.$key.'_t') }}</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ __('landing.steps.'.$key.'_b', ['app' => $appName]) }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Metrics band intentionally removed: bring back when we have real telemetry to publish. --}}

    {{-- ============== PRICING ============== --}}
    <section id="pricing" class="py-24 lg:py-28">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center">
                <span class="inline-block text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">{{ __('landing.pricing.eyebrow') }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight text-slate-900">{{ __('landing.pricing.h2') }}</h2>
                <p class="mt-4 text-lg text-slate-600">{{ __('landing.pricing.sub') }}</p>
            </div>

            <div class="mt-16 grid lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
                {{-- Starter --}}
                <article class="rounded-2xl border border-slate-200 bg-white p-7 flex flex-col">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('landing.pricing.starter_t') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('landing.pricing.starter_d') }}</p>
                    <div class="mt-6 flex items-baseline gap-1">
                        <span class="text-4xl font-extrabold tracking-tight">$49</span>
                        <span class="text-sm font-medium text-slate-500">{{ __('landing.pricing.per_year') }}</span>
                    </div>
                    <a href="@auth{{ route('customer.checkout', ['plan' => 'starter']) }}@else{{ route('register') }}@endauth"
                       class="mt-6 inline-flex justify-center items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition">
                        {{ __('landing.pricing.starter_cta') }}
                    </a>
                    <ul class="mt-7 space-y-3 text-sm text-slate-600 flex-1">
                        @foreach(['starter_f1', 'starter_f2', 'starter_f3', 'starter_f4'] as $f)
                            <li class="flex gap-2.5"><svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-emerald-500 flex-none mt-0.5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>{{ __('landing.pricing.'.$f) }}</li>
                        @endforeach
                    </ul>
                </article>

                {{-- Professional (popular) --}}
                <article class="relative rounded-2xl border-2 border-indigo-600 bg-gradient-to-b from-white to-indigo-50/30 p-7 flex flex-col shadow-xl shadow-indigo-900/10">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-3 py-1 text-xs font-semibold text-white shadow-lg shadow-indigo-600/30">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"/></svg>
                        {{ __('landing.pricing.pro_badge') }}
                    </span>
                    <h3 class="text-base font-semibold text-slate-900">{{ __('landing.pricing.pro_t') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('landing.pricing.pro_d') }}</p>
                    <div class="mt-6 flex items-baseline gap-1">
                        <span class="text-4xl font-extrabold tracking-tight">$99</span>
                        <span class="text-sm font-medium text-slate-500">{{ __('landing.pricing.per_year') }}</span>
                    </div>
                    <a href="@auth{{ route('customer.checkout', ['plan' => 'pro']) }}@else{{ route('register') }}@endauth"
                       class="mt-6 inline-flex justify-center items-center gap-1.5 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 transition shadow-lg shadow-indigo-600/30">
                        {{ __('landing.pricing.pro_cta') }}
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                    </a>
                    <ul class="mt-7 space-y-3 text-sm text-slate-700 flex-1">
                        @foreach(['pro_f1', 'pro_f2', 'pro_f3', 'pro_f4', 'pro_f5', 'pro_f6'] as $f)
                            <li class="flex gap-2.5"><svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-indigo-600 flex-none mt-0.5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>{{ __('landing.pricing.'.$f) }}</li>
                        @endforeach
                    </ul>
                </article>

                {{-- Agency --}}
                <article class="rounded-2xl border border-slate-200 bg-white p-7 flex flex-col">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('landing.pricing.agency_t') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('landing.pricing.agency_d') }}</p>
                    <div class="mt-6 flex items-baseline gap-1">
                        <span class="text-4xl font-extrabold tracking-tight">$199</span>
                        <span class="text-sm font-medium text-slate-500">{{ __('landing.pricing.per_year') }}</span>
                    </div>
                    <a href="@auth{{ route('customer.checkout', ['plan' => 'agency']) }}@else{{ route('register') }}@endauth"
                       class="mt-6 inline-flex justify-center items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition">
                        {{ __('landing.pricing.agency_cta') }}
                    </a>
                    <ul class="mt-7 space-y-3 text-sm text-slate-600 flex-1">
                        @foreach(['agency_f1', 'agency_f2', 'agency_f3', 'agency_f4', 'agency_f5', 'agency_f6'] as $f)
                            <li class="flex gap-2.5"><svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-emerald-500 flex-none mt-0.5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>{{ __('landing.pricing.'.$f) }}</li>
                        @endforeach
                    </ul>
                </article>
            </div>

            <p class="mt-8 text-center text-sm text-slate-500">{{ __('landing.pricing.note') }} <a href="mailto:sales@cookiely.site" class="text-indigo-600 font-semibold hover:underline">{{ __('landing.pricing.contact') }}</a>.</p>
        </div>
    </section>

    {{-- ============== FAQ ============== --}}
    <section id="faq" class="py-24 lg:py-28 bg-slate-50/60 border-y border-slate-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="inline-block text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">{{ __('landing.faq.eyebrow') }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight text-slate-900">{{ __('landing.faq.h2') }}</h2>
            </div>

            <div class="mt-12 space-y-3">
                @foreach(['1', '2', '3', '4', '5', '6'] as $i)
                    <details class="group rounded-xl border border-slate-200 bg-white px-5 py-4 open:shadow-md transition">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-semibold text-slate-900">
                            {{ __('landing.faq.q'.$i) }}
                            <svg class="chev w-5 h-5 text-slate-400 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                        </summary>
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ __('landing.faq.a'.$i, ['app' => $appName]) }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============== FINAL CTA ============== --}}
    <section class="py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-900 to-violet-900 px-8 py-16 sm:px-16 sm:py-20 text-center">
                <div class="absolute inset-0 opacity-50" aria-hidden="true">
                    <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-indigo-500/30 blur-3xl"></div>
                    <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-violet-500/30 blur-3xl"></div>
                </div>
                <div class="relative">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white">
                        {{ __('landing.cta.h2_a') }}<br class="hidden sm:block"> {{ __('landing.cta.h2_b') }}
                    </h2>
                    <p class="mt-5 text-lg text-indigo-100 max-w-xl mx-auto">{{ __('landing.cta.sub') }}</p>
                    <div class="mt-9 flex flex-col sm:flex-row justify-center gap-3">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100 transition shadow-lg">
                            {{ __('landing.cta.primary') }}
                            <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                        </a>
                        <a href="#pricing" class="inline-flex items-center justify-center rounded-lg border border-white/20 bg-white/5 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">
                            {{ __('landing.cta.secondary') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============== FOOTER ============== --}}
    <footer class="bg-slate-950 text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid md:grid-cols-2 lg:grid-cols-12 gap-10">
                <div class="lg:col-span-5">
                    <a href="{{ route('landing') }}" class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-violet-500 text-white">
                            <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5h.01"/><path d="M15.5 11.5h.01"/><path d="M11 14.5h.01"/></svg>
                        </span>
                        <span class="text-lg font-bold text-white">{{ $appName }}</span>
                    </a>
                    <p class="mt-4 text-sm leading-relaxed max-w-sm">{{ __('landing.footer.tagline') }}</p>
                </div>

                <div class="lg:col-span-2">
                    <h4 class="text-sm font-semibold text-white">{{ __('landing.footer.product') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a class="hover:text-white transition" href="#features">{{ __('landing.nav.features') }}</a></li>
                        <li><a class="hover:text-white transition" href="#showcase">{{ __('landing.nav.showcase') }}</a></li>
                        <li><a class="hover:text-white transition" href="#pricing">{{ __('landing.nav.pricing') }}</a></li>
                        <li><a class="hover:text-white transition" href="#faq">{{ __('landing.nav.faq') }}</a></li>
                    </ul>
                </div>

                <div class="lg:col-span-2">
                    <h4 class="text-sm font-semibold text-white">{{ __('landing.footer.account') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a class="hover:text-white transition" href="{{ route('login') }}">{{ __('landing.footer.sign_in') }}</a></li>
                        <li><a class="hover:text-white transition" href="{{ route('register') }}">{{ __('landing.footer.create_account') }}</a></li>
                    </ul>
                </div>

                <div class="lg:col-span-3">
                    <h4 class="text-sm font-semibold text-white">{{ __('landing.footer.legal') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a class="hover:text-white transition" href="{{ route('privacy-policy') }}">{{ __('landing.footer.privacy') }}</a></li>
                        <li><a class="hover:text-white transition" href="{{ route('terms-of-service') }}">{{ __('landing.footer.terms') }}</a></li>
                        <li><a class="hover:text-white transition" href="mailto:privacy@cookiely.site">privacy@cookiely.site</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-14 pt-8 border-t border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs">
                <span>&copy; {{ date('Y') }} {{ $appName }}. {{ __('landing.footer.rights') }}</span>
                <div class="flex items-center gap-5">
                    <span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> {{ __('landing.footer.status') }}</span>
                    <a href="{{ route('privacy-policy') }}" class="hover:text-white transition">{{ __('landing.footer.privacy') }}</a>
                    <a href="{{ route('terms-of-service') }}" class="hover:text-white transition">{{ __('landing.footer.terms') }}</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
