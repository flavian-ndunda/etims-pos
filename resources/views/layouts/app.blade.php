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
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6">
                <span class="font-bold text-gray-800">🇰🇪 eTIMS POS</span>
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('dashboard') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('dashboard') ? 'font-semibold text-blue-600' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('pos.index') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('pos.*') ? 'font-semibold text-blue-600' : '' }}">
                        POS Terminal
                    </a>
                    <a href="{{ route('invoices.index') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('invoices.*') ? 'font-semibold text-blue-600' : '' }}">
                        Invoices
                    </a>
                    <a href="{{ route('invoices.failed') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('invoices.failed') ? 'font-semibold text-red-600' : '' }}">
                        Failed
                    </a>
                    <a href="{{ route('mpesa.index') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('mpesa.*') ? 'font-semibold text-green-600' : '' }}">
                        📱 M-Pesa
                    </a>
                    <a href="{{ route('sync.dashboard') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('sync.*') ? 'font-semibold text-yellow-600' : '' }}">
                        Sync
                    </a> 
                    <a href="{{ route('products.categories') }}"
   class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('products.categories*') ? 'font-semibold text-blue-600' : '' }}">
    Categories
</a>
                    <a href="{{ route('products.index') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('products.*') ? 'font-semibold text-blue-600' : '' }}">
                        Products
                    </a>
                </div>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-500">
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="text-red-500 hover:text-red-700">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="mx-4 mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
        ✅ {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mx-4 mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
        ❌ {{ session('error') }}
    </div>
    @endif

    <!-- Header -->
    @isset($header)
    <header class="bg-white border-b border-gray-200 px-6 py-4">
        {{ $header }}
    </header>
    @endisset

    <!-- Main Content -->
    <main>{{ $slot }}</main>

    @stack('scripts')
</body>
</html>
