<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('pos.business_name', 'eTIMS POS') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
        .nav-link { @apply text-gray-600 hover:text-gray-900 hover:bg-gray-100 px-3 py-2 rounded-lg text-sm transition-colors; }
        .nav-link.active { @apply font-semibold text-blue-600 bg-blue-50; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 px-4 py-2 sticky top-0 z-50 shadow-sm">
        <div class="flex items-center justify-between">

            <!-- Left: Logo + Back button + Nav links -->
            <div class="flex items-center gap-2">

                <!-- Back Button -->
                @if(!request()->routeIs('dashboard') && !request()->routeIs('pos.index'))
                <button onclick="history.back()"
                        class="flex items-center gap-1 text-gray-500 hover:text-gray-800 hover:bg-gray-100 px-2 py-1.5 rounded-lg text-sm transition-colors mr-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </button>
                <div class="w-px h-5 bg-gray-200"></div>
                @endif

                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 py-1">
                    <span class="text-lg">🇰🇪</span>
                    <span class="font-bold text-gray-800 text-sm hidden sm:block">eTIMS POS</span>
                </a>

                <!-- Nav Links -->
                <div class="flex items-center gap-1 ml-2">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        📊 Dashboard
                    </a>
                    <a href="{{ route('pos.index') }}"
                       class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}">
                        🛒 POS
                    </a>
                    <a href="{{ route('invoices.index') }}"
                       class="nav-link {{ request()->routeIs('invoices.*') && !request()->routeIs('invoices.failed') ? 'active' : '' }}">
                        🧾 Invoices
                    </a>
                    <a href="{{ route('invoices.failed') }}"
                       class="nav-link {{ request()->routeIs('invoices.failed') ? 'active text-red-600 bg-red-50' : '' }}">
                        ❌ Failed
                    </a>
                    <a href="{{ route('mpesa.index') }}"
                       class="nav-link {{ request()->routeIs('mpesa.*') ? 'active text-green-600 bg-green-50' : '' }}">
                        📱 M-Pesa
                    </a>
                    <a href="{{ route('sync.dashboard') }}"
                       class="nav-link {{ request()->routeIs('sync.*') ? 'active text-yellow-600 bg-yellow-50' : '' }}">
                        🔄 Sync
                    </a>
                    <a href="{{ route('products.index') }}"
                       class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        📦 Products
                    </a>
                </div>
            </div>

            <!-- Right: User info + logout -->
            <div class="flex items-center gap-3">
                <div class="text-xs text-gray-400 hidden md:block">
                    {{ now()->format('D, d M H:i') }}
                </div>
                <div class="flex items-center gap-2 border-l pl-3">
                    <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit"
                                class="text-xs text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded transition-colors">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="mx-4 mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
        <span>✅</span> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mx-4 mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
        <span>❌</span> {{ session('error') }}
    </div>
    @endif

    <!-- Page Header -->
    @isset($header)
    <header class="bg-white border-b border-gray-200 px-6 py-4 mt-0">
        {{ $header }}
    </header>
    @endisset

    <!-- Main Content -->
    <main>{{ $slot }}</main>

    @stack('scripts')
</body>
</html>