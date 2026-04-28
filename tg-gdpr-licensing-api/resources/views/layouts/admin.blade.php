<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#4f46e5">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-blue-400">{{ config('app.name') }}</h1>
                <p class="text-gray-400 text-sm">Compliance Dashboard</p>
            </div>
            
            <nav class="mt-6">
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                
                <a href="{{ route('admin.customers.index') }}" 
                   class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.customers.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-users w-5"></i>
                    <span class="ml-3">Customers</span>
                </a>
                
                <a href="{{ route('admin.licenses.index') }}" 
                   class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.licenses.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-key w-5"></i>
                    <span class="ml-3">Licenses</span>
                </a>
            </nav>

                <a href="{{ route('admin.sites.index') }}"
                   class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.sites.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-globe w-5"></i>
                    <span class="ml-3">Sites</span>
                </a>

                <a href="{{ route('admin.dsar.index') }}"
                   class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.dsar.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-user-shield w-5"></i>
                    <span class="ml-3">DSARs</span>
                </a>

                <a href="{{ route('admin.plans.index') }}"
                   class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.plans.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-tag w-5"></i>
                    <span class="ml-3">Plans</span>
                </a>

                <a href="{{ route('admin.settings.payments') }}"
                   class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.settings.payments*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-credit-card w-5"></i>
                    <span class="ml-3">Payments</span>
                </a>

                <a href="{{ route('admin.settings.mail') }}"
                   class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.settings.mail*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-cog w-5"></i>
                    <span class="ml-3">Mail</span>
                </a>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-8 py-4">
                    <h2 class="text-2xl font-semibold text-gray-800">
                        @yield('page-title', 'Dashboard')
                    </h2>

                    <div class="flex items-center gap-4">
                        <span class="hidden md:inline text-sm text-gray-500">{{ now()->format('M d, Y') }}</span>

                        {{-- User menu --}}
                        <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                            <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-2 rounded-full bg-gray-100 hover:bg-gray-200 pl-3 pr-2 py-1.5 transition"
                                    :aria-expanded="open" aria-haspopup="menu">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-white text-xs font-bold uppercase">
                                    {{ Str::substr(Auth::user()->name, 0, 1) }}
                                </span>
                                <span class="text-sm font-medium text-gray-800">{{ Auth::user()->name }}</span>
                                <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-500">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.outside="open = false" x-cloak x-transition
                                 class="absolute right-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-lg shadow-slate-900/5 py-2 z-50" role="menu">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <div class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user-cog w-4 text-gray-400"></i> Profile
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100 mt-1 pt-1">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                        <i class="fas fa-sign-out-alt w-4 text-red-500"></i> Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-8">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
