<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eTIMS POS — Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🇰🇪</div>
            <h1 class="text-2xl font-bold text-gray-900">eTIMS POS</h1>
            <p class="text-gray-500 text-sm mt-1">KRA eTIMS + M-Pesa Integration</p>
        </div>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="/login" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', 'admin@demo.co.ke') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required autofocus>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" value="password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
            </div>
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition-colors">
                Sign In
            </button>
        </form>

        <div class="mt-6 p-4 bg-gray-50 rounded-lg text-xs text-gray-500 space-y-1">
            <div><strong>Demo credentials:</strong></div>
            <div>📧 admin@demo.co.ke</div>
            <div>🔑 password</div>
        </div>
    </div>
</body>
</html>
