<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - GDPR Compliance Made Easy</title>
    <meta name="description" content="Professional GDPR compliant cookie consent platform for modern websites. Easy setup, customizable, and fully aligned with EU regulations.">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-indigo-600">
                        {{ config('app.name') }}
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-indigo-600">Features</a>
                    <a href="#pricing" class="text-gray-700 hover:text-indigo-600">Pricing</a>
                    <a href="#faq" class="text-gray-700 hover:text-indigo-600">FAQ</a>
                    <a href="#contact" class="text-gray-700 hover:text-indigo-600">Contact</a>
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard') }}" class="text-gray-700 hover:text-indigo-600">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-indigo-600">Login</a>
                        <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Get Started</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    GDPR Compliance Made Simple
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-indigo-100">
                    Professional cookie consent platform for modern websites. EU compliant, customizable, and easy to use.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#pricing" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                        View Pricing
                    </a>
                    <a href="#download" class="bg-transparent border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition">
                        Try Free Version
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Everything You Need for GDPR Compliance
                </h2>
                <p class="text-xl text-gray-600">
                    Powerful features to keep your website compliant
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="text-indigo-600 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">100% GDPR Compliant</h3>
                    <p class="text-gray-600">
                        Fully compliant with EU GDPR regulations. Get consent before setting cookies.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="text-indigo-600 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Fully Customizable</h3>
                    <p class="text-gray-600">
                        Match your brand with custom colors, fonts, and positioning options.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="text-indigo-600 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Lightning Fast</h3>
                    <p class="text-gray-600">
                        Optimized code that doesn't slow down your website. Under 10KB gzipped.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="text-indigo-600 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Mobile Responsive</h3>
                    <p class="text-gray-600">
                        Perfect display on all devices. Touch-friendly and accessible.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="text-indigo-600 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Easy Setup</h3>
                    <p class="text-gray-600">
                        Install and configure in minutes. No coding required.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="text-indigo-600 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Priority Support</h3>
                    <p class="text-gray-600">
                        Get help when you need it with our dedicated support team.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Simple, Transparent Pricing
                </h2>
                <p class="text-xl text-gray-600">
                    Choose the perfect plan for your needs
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Starter Plan -->
                <div class="bg-white border-2 border-gray-200 rounded-lg p-8">
                    <h3 class="text-2xl font-bold mb-4">Starter</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold">$49</span>
                        <span class="text-gray-600">/year</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Single site license
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            GDPR compliant
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Customizable design
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            1 year updates
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block text-center bg-gray-900 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-800 transition">
                        Get Started
                    </a>
                </div>

                <!-- Professional Plan (Popular) -->
                <div class="bg-indigo-600 text-white border-2 border-indigo-600 rounded-lg p-8 transform scale-105 shadow-xl">
                    <div class="bg-white text-indigo-600 text-sm font-bold px-3 py-1 rounded-full inline-block mb-4">
                        MOST POPULAR
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Professional</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold">$99</span>
                        <span class="text-indigo-100">/year</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-200 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Up to 5 sites
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-200 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            GDPR compliant
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-200 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Advanced customization
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-200 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Priority support
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-200 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Commercial use
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block text-center bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                        Get Started
                    </a>
                </div>

                <!-- Agency Plan -->
                <div class="bg-white border-2 border-gray-200 rounded-lg p-8">
                    <h3 class="text-2xl font-bold mb-4">Agency</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold">$199</span>
                        <span class="text-gray-600">/year</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Up to 25 sites
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            White-label options
                        </li>
                        <li class="flex items-center">
                    <!DOCTYPE html>
                    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>{{ config('app.name') }} — GDPR Cookie Consent for WordPress</title>
                        <meta name="description" content="The smartest GDPR & cookie consent platform for WordPress. Customizable banners, Google Consent Mode v2, DSAR management, and more.">
                        @vite(['resources/css/app.css', 'resources/js/app.js'])
                        <style>
                            /* Landing page custom styles */
                            .hero-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(79,70,229,.08); color: #4f46e5; font-size: 13px; font-weight: 600; padding: 6px 14px; border-radius: 999px; border: 1px solid rgba(79,70,229,.2); }
                            .trust-badge { display: inline-flex; align-items: center; gap: 7px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; color: #334155; }
                            .feature-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
                            .step-num { width: 36px; height: 36px; border-radius: 50%; background: #4f46e5; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
                            .pricing-card { background: white; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 32px; transition: box-shadow .2s; }
                            .pricing-card:hover { box-shadow: 0 8px 32px rgba(79,70,229,.12); }
                            .pricing-card-popular { border-color: #4f46e5; box-shadow: 0 8px 32px rgba(79,70,229,.18); }
                            .faq-item summary { cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between; }
                            .faq-item summary::-webkit-details-marker { display: none; }
                            .faq-item[open] summary svg { transform: rotate(180deg); }
                            .faq-item summary svg { transition: transform .25s; flex-shrink: 0; }
                            /* Banner preview mockup */
                            .banner-preview { background: white; border-radius: 12px; box-shadow: 0 4px 32px rgba(0,0,0,.15); overflow: hidden; border: 1px solid #e2e8f0; }
                            .banner-preview-bar { background: white; border-top: 3px solid #4f46e5; padding: 14px 20px 10px; }
                            .banner-preview-inner { display: flex; align-items: center; gap: 12px; }
                            .banner-preview-icon { width: 36px; height: 36px; background: rgba(79,70,229,.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #4f46e5; flex-shrink: 0; }
                            .banner-preview-text { flex: 1; }
                            .banner-preview-title { font-size: 13px; font-weight: 700; color: #0f172a; }
                            .banner-preview-msg { font-size: 11px; color: #64748b; margin-top: 2px; }
                            .banner-preview-btns { display: flex; gap: 6px; flex-shrink: 0; }
                            .bp-btn { font-size: 11px; font-weight: 600; padding: 5px 10px; border-radius: 6px; border: 1.5px solid; cursor: default; }
                            .bp-btn-reject { background: transparent; color: #64748b; border-color: transparent; }
                            .bp-btn-pref { background: transparent; color: #4f46e5; border-color: rgba(79,70,229,.4); }
                            .bp-btn-accept { background: #4f46e5; color: white; border-color: #4f46e5; }
                            .banner-preview-brand { display: flex; justify-content: flex-end; padding: 4px 0 2px; font-size: 10px; color: #94a3b8; }
                            /* Fake browser chrome for mockup */
                            .browser-chrome { background: #f1f5f9; border-radius: 14px 14px 0 0; padding: 10px 16px; display: flex; align-items: center; gap: 6px; }
                            .browser-dot { width: 10px; height: 10px; border-radius: 50%; }
                            .browser-bar { flex: 1; background: white; border-radius: 6px; height: 22px; margin: 0 10px; }
                        </style>
                    </head>
                    <body class="antialiased bg-white text-slate-900" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

                        <!-- Navigation -->
                        <nav class="bg-white border-b border-slate-100 sticky top-0 z-50">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                                <div class="flex justify-between h-16 items-center">
                                    <a href="/" class="flex items-center gap-2 font-bold text-xl text-slate-900">
                                        <span style="font-size:22px;">🍪</span>
                                        <span>{{ config('app.name') }}</span>
                                    </a>
                                    <div class="hidden md:flex items-center gap-8">
                                        <a href="#features" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition">Features</a>
                                        <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition">How it works</a>
                                        <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition">Pricing</a>
                                        <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition">FAQ</a>
                                        @auth
                                            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Dashboard →</a>
                                        @else
                                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition">Sign in</a>
                                            <a href="{{ route('register') }}" class="text-sm font-semibold bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition shadow-sm">Get Started Free</a>
                                        @endauth
                                    </div>
                                    <!-- Mobile menu toggle -->
                                    <button class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                    </button>
                                </div>
                                <div id="mobile-menu" class="hidden md:hidden pb-4">
                                    <div class="flex flex-col gap-3">
                                        <a href="#features" class="text-sm font-medium text-slate-600">Features</a>
                                        <a href="#pricing" class="text-sm font-medium text-slate-600">Pricing</a>
                                        <a href="#faq" class="text-sm font-medium text-slate-600">FAQ</a>
                                        @auth
                                            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard') }}" class="text-sm font-medium text-indigo-600">Dashboard →</a>
                                        @else
                                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600">Sign in</a>
                                            <a href="{{ route('register') }}" class="text-sm font-semibold bg-indigo-600 text-white px-4 py-2 rounded-lg text-center">Get Started Free</a>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </nav>

                        <!-- Hero Section -->
                        <section class="pt-20 pb-16 px-4 sm:px-6 lg:px-8" style="background: linear-gradient(160deg, #fafafe 0%, #eef2ff 100%);">
                            <div class="max-w-7xl mx-auto">
                                <div class="grid lg:grid-cols-2 gap-16 items-center">
                                    <!-- Left: Copy -->
                                    <div>
                                        <div class="mb-6">
                                            <span class="hero-badge">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                                                Google Consent Mode v2 ready
                                            </span>
                                        </div>
                                        <h1 class="text-4xl sm:text-5xl font-extrabold text-slate-900 leading-tight mb-5" style="letter-spacing: -0.03em;">
                                            GDPR cookie consent<br>
                                            <span style="color:#4f46e5;">your visitors will trust</span>
                                        </h1>
                                        <p class="text-lg text-slate-500 mb-8 leading-relaxed">
                                            {{ config('app.name') }} is a WordPress plugin that makes GDPR, CCPA, and ePrivacy compliance effortless — with a beautiful consent banner, automatic cookie scanning, and full audit logs.
                                        </p>
                                        <div class="flex flex-wrap gap-3 mb-10">
                                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-indigo-600 text-white font-semibold px-6 py-3 rounded-xl hover:bg-indigo-700 transition shadow-md text-sm">
                                                Get Started Free
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                            </a>
                                            <a href="#pricing" class="inline-flex items-center gap-2 bg-white text-slate-700 font-semibold px-6 py-3 rounded-xl border border-slate-200 hover:border-indigo-300 hover:text-indigo-600 transition text-sm">
                                                View Pricing
                                            </a>
                                        </div>
                                        <!-- Trust badges -->
                                        <div class="flex flex-wrap gap-3">
                                            <span class="trust-badge">
                                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                GDPR Ready
                                            </span>
                                            <span class="trust-badge">
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                                                CCPA Compliant
                                            </span>
                                            <span class="trust-badge">
                                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>
                                                Google Consent v2
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Right: Banner Mockup -->
                                    <div class="relative">
                                        <div style="background: #e8eaf6; border-radius: 20px; padding: 8px 8px 0; box-shadow: 0 20px 60px rgba(79,70,229,.15);">
                                            <!-- Browser Chrome -->
                                            <div class="browser-chrome">
                                                <div class="browser-dot" style="background:#fc5c57;"></div>
                                                <div class="browser-dot" style="background:#ffbd2e;"></div>
                                                <div class="browser-dot" style="background:#28c840;"></div>
                                                <div class="browser-bar"></div>
                                            </div>
                                            <!-- Fake page content -->
                                            <div style="background: #f8fafc; padding: 24px 20px 0; min-height: 200px; position: relative; overflow: hidden;">
                                                <div style="display:flex;gap:10px;margin-bottom:12px;">
                                                    <div style="height:10px;background:#e2e8f0;border-radius:4px;flex:2;"></div>
                                                    <div style="height:10px;background:#e2e8f0;border-radius:4px;flex:1;"></div>
                                                </div>
                                                <div style="height:8px;background:#e2e8f0;border-radius:4px;margin-bottom:8px;width:90%;"></div>
                                                <div style="height:8px;background:#e2e8f0;border-radius:4px;margin-bottom:8px;width:75%;"></div>
                                                <div style="height:8px;background:#e2e8f0;border-radius:4px;margin-bottom:20px;width:85%;"></div>
                                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                                    <div style="height:70px;background:#e2e8f0;border-radius:8px;"></div>
                                                    <div style="height:70px;background:#e2e8f0;border-radius:8px;"></div>
                                                </div>
                                                <!-- Cookie Banner Overlay -->
                                                <div class="banner-preview" style="position:absolute;bottom:0;left:0;right:0;">
                                                    <div class="banner-preview-bar">
                                                        <div class="banner-preview-inner">
                                                            <div class="banner-preview-icon">
                                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="9" r="1.5"/><circle cx="15" cy="7" r="1"/><circle cx="7" cy="14" r="1"/><circle cx="13" cy="15" r="1.5"/><path d="M21.93 10.35a1 1 0 0 0-.93-.35 3 3 0 0 1-3.27-3.64 1 1 0 0 0-1.24-1.18 3 3 0 0 1-3.42-1.46 1 1 0 0 0-1.8.14A9 9 0 1 0 21.93 10.35z"/></svg>
                                                            </div>
                                                            <div class="banner-preview-text">
                                                                <div class="banner-preview-title">We value your privacy</div>
                                                                <div class="banner-preview-msg">We use cookies to enhance your browsing experience.</div>
                                                            </div>
                                                            <div class="banner-preview-btns">
                                                                <span class="bp-btn bp-btn-reject">Reject</span>
                                                                <span class="bp-btn bp-btn-pref">Preferences</span>
                                                                <span class="bp-btn bp-btn-accept">Accept All</span>
                                                            </div>
                                                        </div>
                                                        <div class="banner-preview-brand">Powered by <strong style="margin-left:3px;">Cookiely</strong></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- How It Works -->
                        <section id="how-it-works" class="py-20 bg-white">
                            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                                <div class="text-center mb-14">
                                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-3" style="letter-spacing:-0.02em;">Up and running in minutes</h2>
                                    <p class="text-slate-500 text-lg">No developer needed. Just install, connect, and go live.</p>
                                </div>
                                <div class="grid sm:grid-cols-3 gap-8">
                                    <div class="flex flex-col items-start gap-4">
                                        <div class="step-num">1</div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 text-lg mb-1">Install the plugin</h3>
                                            <p class="text-slate-500 text-sm leading-relaxed">Download and install the {{ config('app.name') }} WordPress plugin from your dashboard. Activate it in one click.</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-start gap-4">
                                        <div class="step-num">2</div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 text-lg mb-1">Activate your license</h3>
                                            <p class="text-slate-500 text-sm leading-relaxed">Paste your license key into the plugin settings. Your site is instantly connected to the Cookiely cloud.</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-start gap-4">
                                        <div class="step-num">3</div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 text-lg mb-1">Go live</h3>
                                            <p class="text-slate-500 text-sm leading-relaxed">Customise your banner colours, texts, and position. Save — your visitors are now asked for consent automatically.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Features Section -->
                        <section id="features" class="py-20" style="background:#fafafe;">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                                <div class="text-center mb-14">
                                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-3" style="letter-spacing:-0.02em;">
                                        Everything you need, nothing you don't
                                    </h2>
                                    <p class="text-slate-500 text-lg">Built specifically for GDPR, ePrivacy, and beyond.</p>
                                </div>

                                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <!-- Feature 1 -->
                                    <div class="bg-white p-7 rounded-2xl border border-slate-100 hover:shadow-lg hover:border-indigo-100 transition">
                                        <div class="feature-icon bg-indigo-50 mb-4">
                                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </div>
                                        <h3 class="font-bold text-slate-900 text-lg mb-2">100% GDPR & ePrivacy</h3>
                                        <p class="text-slate-500 text-sm leading-relaxed">Prior consent, granular categories, and full audit logs — everything the regulators expect.</p>
                                    </div>

                                    <!-- Feature 2 -->
                                    <div class="bg-white p-7 rounded-2xl border border-slate-100 hover:shadow-lg hover:border-indigo-100 transition">
                                        <div class="feature-icon bg-orange-50 mb-4">
                                            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                            </svg>
                                        </div>
                                        <h3 class="font-bold text-slate-900 text-lg mb-2">Fully Customizable Banner</h3>
                                        <p class="text-slate-500 text-sm leading-relaxed">Match your brand perfectly. Change colours, fonts, position, button styles, and text — no coding.</p>
                                    </div>

                                    <!-- Feature 3 -->
                                    <div class="bg-white p-7 rounded-2xl border border-slate-100 hover:shadow-lg hover:border-indigo-100 transition">
                                        <div class="feature-icon bg-red-50 mb-4">
                                            <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M21.35 11.1C20.83 7.63 17.76 5 14 5c-3.76 0-6.83 2.63-7.35 6.1A4 4 0 0 0 8 19h12a4 4 0 0 0 1.35-7.9z"/>
                                            </svg>
                                        </div>
                                        <h3 class="font-bold text-slate-900 text-lg mb-2">Google Consent Mode v2</h3>
                                        <p class="text-slate-500 text-sm leading-relaxed">Automatically signals consent state to Google Ads and Analytics — keep your conversion tracking compliant.</p>
                                    </div>

                                    <!-- Feature 4 -->
                                    <div class="bg-white p-7 rounded-2xl border border-slate-100 hover:shadow-lg hover:border-indigo-100 transition">
                                        <div class="feature-icon bg-green-50 mb-4">
                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </div>
                                        <h3 class="font-bold text-slate-900 text-lg mb-2">Lightning Fast</h3>
                                        <p class="text-slate-500 text-sm leading-relaxed">Under 10 KB gzipped. Zero render-blocking. Your site stays fast, your Core Web Vitals stay green.</p>
                                    </div>

                                    <!-- Feature 5 -->
                                    <div class="bg-white p-7 rounded-2xl border border-slate-100 hover:shadow-lg hover:border-indigo-100 transition">
                                        <div class="feature-icon bg-purple-50 mb-4">
                                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                        </div>
                                        <h3 class="font-bold text-slate-900 text-lg mb-2">DSAR Management</h3>
                                        <p class="text-slate-500 text-sm leading-relaxed">Handle Data Subject Access Requests automatically. Email verification, fulfilment tracking, and audit trail included.</p>
                                    </div>

                                    <!-- Feature 6 -->
                                    <div class="bg-white p-7 rounded-2xl border border-slate-100 hover:shadow-lg hover:border-indigo-100 transition">
                                        <div class="feature-icon bg-sky-50 mb-4">
                                            <svg class="w-6 h-6 text-sky-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                            </svg>
                                        </div>
                                        <h3 class="font-bold text-slate-900 text-lg mb-2">Consent Analytics</h3>
                                        <p class="text-slate-500 text-sm leading-relaxed">See accept, reject, and preference rates over time. Know exactly how visitors interact with your banner.</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Pricing Section -->
                        <section id="pricing" class="py-20 bg-white">
                            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                                <div class="text-center mb-14">
                                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-3" style="letter-spacing:-0.02em;">Simple, transparent pricing</h2>
                                    <p class="text-slate-500 text-lg">One-time annual fee. No hidden costs. Cancel anytime.</p>
                                </div>

                                <div class="grid md:grid-cols-3 gap-6 items-start">
                                    <!-- Starter Plan -->
                                    <div class="pricing-card">
                                        <div class="mb-6">
                                            <h3 class="text-lg font-bold text-slate-900 mb-1">Starter</h3>
                                            <p class="text-sm text-slate-500">Perfect for personal sites and blogs.</p>
                                        </div>
                                        <div class="mb-6">
                                            <span class="text-4xl font-extrabold text-slate-900" style="letter-spacing:-0.02em;">$49</span>
                                            <span class="text-slate-400 text-sm">/year</span>
                                        </div>
                                        <ul class="space-y-3 mb-8 text-sm text-slate-600">
                                            @foreach(['1 site license', 'GDPR & ePrivacy compliant', 'Customizable banner', 'Consent logs', '1 year updates & support'] as $f)
                                            <li class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                                {{ $f }}
                                            </li>
                                            @endforeach
                                        </ul>
                                        <a href="{{ route('register') }}" class="block text-center bg-slate-900 text-white text-sm font-semibold px-5 py-3 rounded-xl hover:bg-slate-700 transition">
                                            Get Started
                                        </a>
                                    </div>

                                    <!-- Professional Plan (Popular) -->
                                    <div class="pricing-card pricing-card-popular relative">
                                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                            <span class="bg-indigo-600 text-white text-xs font-bold px-4 py-1 rounded-full">MOST POPULAR</span>
                                        </div>
                                        <div class="mb-6">
                                            <h3 class="text-lg font-bold text-slate-900 mb-1">Professional</h3>
                                            <p class="text-sm text-slate-500">For growing businesses and freelancers.</p>
                                        </div>
                                        <div class="mb-6">
                                            <span class="text-4xl font-extrabold text-slate-900" style="letter-spacing:-0.02em;">$99</span>
                                            <span class="text-slate-400 text-sm">/year</span>
                                        </div>
                                        <ul class="space-y-3 mb-8 text-sm text-slate-600">
                                            @foreach(['Up to 5 sites', 'Everything in Starter', 'Google Consent Mode v2', 'DSAR management', 'Priority support', 'Commercial use'] as $f)
                                            <li class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                                {{ $f }}
                                            </li>
                                            @endforeach
                                        </ul>
                                        <a href="{{ route('register') }}" class="block text-center bg-indigo-600 text-white text-sm font-semibold px-5 py-3 rounded-xl hover:bg-indigo-700 transition shadow-md">
                                            Get Started
                                        </a>
                                    </div>

                                    <!-- Agency Plan -->
                                    <div class="pricing-card">
                                        <div class="mb-6">
                                            <h3 class="text-lg font-bold text-slate-900 mb-1">Agency</h3>
                                            <p class="text-sm text-slate-500">For agencies managing many client sites.</p>
                                        </div>
                                        <div class="mb-6">
                                            <span class="text-4xl font-extrabold text-slate-900" style="letter-spacing:-0.02em;">$199</span>
                                            <span class="text-slate-400 text-sm">/year</span>
                                        </div>
                                        <ul class="space-y-3 mb-8 text-sm text-slate-600">
                                            @foreach(['Up to 25 sites', 'Everything in Professional', 'White-label options', 'Dedicated account manager', 'Lifetime updates', 'Agency toolkit'] as $f)
                                            <li class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                                {{ $f }}
                                            </li>
                                            @endforeach
                                        </ul>
                                        <a href="{{ route('register') }}" class="block text-center bg-slate-900 text-white text-sm font-semibold px-5 py-3 rounded-xl hover:bg-slate-700 transition">
                                            Get Started
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- FAQ Section -->
                        <section id="faq" class="py-20" style="background:#fafafe;">
                            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                                <div class="text-center mb-12">
                                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-3" style="letter-spacing:-0.02em;">Common questions</h2>
                                </div>
                                <div class="space-y-3">
                                    @foreach([
                                        ['What is GDPR and do I need this?', 'GDPR is a European regulation requiring websites to obtain informed consent before setting non-essential cookies. If any of your visitors are in the EU, you are legally obligated to comply — regardless of where your business is located.'],
                                        ['Does this work with Google Analytics & Ads?', 'Yes. Cookiely implements Google Consent Mode v2 — the latest standard from Google. Analytics and Ads scripts are blocked until the visitor grants consent, keeping you compliant without losing conversion data.'],
                                        ['How long does setup take?', 'Under 5 minutes. Install the WordPress plugin, paste your license key, customise the banner to match your brand, and save. That\'s it.'],
                                        ['Can I customise the banner design?', 'Absolutely. Change colours, fonts, button labels, position (bottom bar, corner box, or centre modal), and layout to match your site perfectly — no coding required.'],
                                        ['What happens to my data?', 'Consent logs are stored securely and are accessible in your dashboard. We never sell your data or your visitors\' data. See our Privacy Policy for details.'],
                                        ['Is there a free version?', 'Yes — a free WordPress plugin with basic GDPR compliance is available. Premium plans unlock advanced customisation, Google Consent Mode v2, DSAR management, analytics, and priority support.'],
                                    ] as [$q, $a])
                                    <details class="faq-item bg-white border border-slate-100 rounded-2xl">
                                        <summary class="p-5 font-semibold text-slate-900 text-sm">
                                            {{ $q }}
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                                        </summary>
                                        <p class="px-5 pb-5 text-sm text-slate-500 leading-relaxed">{{ $a }}</p>
                                    </details>
                                    @endforeach
                                </div>
                            </div>
                        </section>

                        <!-- CTA Section -->
                        <section id="download" class="py-20 bg-white">
                            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                                <div style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 24px; padding: 60px 40px;">
                                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4" style="letter-spacing:-0.02em;">
                                        Start for free today
                                    </h2>
                                    <p class="text-indigo-200 text-lg mb-8">
                                        Create your account and get your first site compliant in under 5 minutes.
                                    </p>
                                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 bg-white text-indigo-700 font-bold px-8 py-3 rounded-xl hover:bg-indigo-50 transition text-sm shadow-lg">
                                            Create Free Account
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                        </a>
                                        <a href="#pricing" class="inline-flex items-center justify-center bg-transparent border-2 border-white text-white font-semibold px-8 py-3 rounded-xl hover:bg-white hover:text-indigo-700 transition text-sm">
                                            View Pricing
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Footer -->
                        <footer class="bg-slate-900 text-slate-400 py-14">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                                <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-8 mb-10">
                                    <div>
                                        <div class="flex items-center gap-2 mb-3">
                                            <span style="font-size:20px;">🍪</span>
                                            <span class="text-white font-bold text-lg">{{ config('app.name') }}</span>
                                        </div>
                                        <p class="text-sm leading-relaxed">GDPR & cookie consent made simple for WordPress websites.</p>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-semibold mb-4 text-sm">Product</h4>
                                        <ul class="space-y-2 text-sm">
                                            <li><a href="#features" class="hover:text-white transition">Features</a></li>
                                            <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
                                            <li><a href="#faq" class="hover:text-white transition">FAQ</a></li>
                                            <li><a href="#how-it-works" class="hover:text-white transition">How it works</a></li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-semibold mb-4 text-sm">Account</h4>
                                        <ul class="space-y-2 text-sm">
                                            <li><a href="{{ route('login') }}" class="hover:text-white transition">Sign In</a></li>
                                            <li><a href="{{ route('register') }}" class="hover:text-white transition">Create Account</a></li>
                                            <li><a href="{{ route('customer.dashboard') }}" class="hover:text-white transition">Customer Portal</a></li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-semibold mb-4 text-sm">Legal</h4>
                                        <ul class="space-y-2 text-sm">
                                            <li><a href="{{ route('privacy-policy') }}" class="hover:text-white transition">Privacy Policy</a></li>
                                            <li><a href="{{ route('terms-of-service') }}" class="hover:text-white transition">Terms of Service</a></li>
                                            <li><a href="mailto:legal@cookiely.io" class="hover:text-white transition">legal@cookiely.io</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="border-t border-slate-800 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-slate-500">
                                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                                    <div class="flex gap-4">
                                        <a href="{{ route('privacy-policy') }}" class="hover:text-slate-300 transition">Privacy</a>
                                        <a href="{{ route('terms-of-service') }}" class="hover:text-slate-300 transition">Terms</a>
                                    </div>
                                </div>
                            </div>
                        </footer>
                    </body>
                    </html>
            <div class="space-y-6">
                <details class="bg-white rounded-lg p-6 shadow-md">
                    <summary class="font-semibold text-lg cursor-pointer">What is GDPR compliance?</summary>
                    <p class="mt-4 text-gray-600">
                        GDPR (General Data Protection Regulation) is a European law that requires websites to get user consent before storing cookies or tracking data. Cookiely helps your site meet these requirements.
                    </p>
                </details>

                <details class="bg-white rounded-lg p-6 shadow-md">
                    <summary class="font-semibold text-lg cursor-pointer">Do I need this if I'm not in Europe?</summary>
                    <p class="mt-4 text-gray-600">
                        If your website has visitors from the EU, you need to be GDPR compliant. Many privacy laws worldwide (like CCPA in California) also have similar requirements.
                    </p>
                </details>

                <details class="bg-white rounded-lg p-6 shadow-md">
                    <summary class="font-semibold text-lg cursor-pointer">How easy is it to install?</summary>
                    <p class="mt-4 text-gray-600">
                        Very easy. Connect Cookiely to your site, activate your license, and configure your preferences. The whole process takes less than 5 minutes.
                    </p>
                </details>

                <details class="bg-white rounded-lg p-6 shadow-md">
                    <summary class="font-semibold text-lg cursor-pointer">Can I customize the banner design?</summary>
                    <p class="mt-4 text-gray-600">
                        Yes! You can customize colors, fonts, positioning, button text, and more to match your brand perfectly.
                    </p>
                </details>

                <details class="bg-white rounded-lg p-6 shadow-md">
                    <summary class="font-semibold text-lg cursor-pointer">What's the difference between free and premium?</summary>
                    <p class="mt-4 text-gray-600">
                        The free version includes basic GDPR compliance. Premium versions include advanced customization, priority support, commercial use license, and automatic updates.
                    </p>
                </details>
            </div>
        </div>
    </section>

    <!-- Download Free Version CTA -->
    <section id="download" class="py-20 bg-indigo-600 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">
                Try the Free Version
            </h2>
            <p class="text-xl mb-8 text-indigo-100">
                Request access to the free version and get setup details by email.
            </p>
            <form action="{{ route('download') }}" method="POST" class="max-w-md mx-auto">
                @csrf
                <div class="flex gap-4">
                    <input type="email" name="email" required placeholder="Enter your email" class="flex-1 px-4 py-3 rounded-lg text-gray-900">
                    <button type="submit" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                        Get Access
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Get In Touch
                </h2>
                <p class="text-xl text-gray-600">
                    Have questions? We're here to help!
                </p>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('contact') }}" method="POST" class="bg-white rounded-lg shadow-md p-8">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                        <input type="text" id="name" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        @error('email')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700 mb-2">Company (optional)</label>
                        <input type="text" id="company" name="company" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="message" name="message" rows="5" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                        @error('message')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">{{ config('app.name') }}</h3>
                    <p class="text-sm">
                        Making GDPR compliance simple for modern websites.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white">Pricing</a></li>
                        <li><a href="#faq" class="hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#contact" class="hover:text-white">Contact</a></li>
                        <li><a href="#" class="hover:text-white">Documentation</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white">Customer Portal</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white">License Agreement</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
