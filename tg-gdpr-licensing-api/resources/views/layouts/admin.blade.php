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
                    
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">{{ now()->format('M d, Y') }}</span>
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
