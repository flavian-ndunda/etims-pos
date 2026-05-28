<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-gray-800">Edit User — {{ $user->name }}</h1>
    </x-slot>

    <div class="p-6 max-w-lg space-y-6">

        {{-- Edit Details --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">User Details</h2>

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm space-y-1">
                @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
                @csrf @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    @if($user->id === auth()->id())
                    <div class="p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500">
                        {{ $user->roleLabel() }} — You cannot change your own role
                    </div>
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    @else
                    <select name="role" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="cashier"  {{ $user->role === 'cashier'  ? 'selected' : '' }}>🛒 Cashier</option>
                        <option value="manager"  {{ $user->role === 'manager'  ? 'selected' : '' }}>📊 Manager</option>
                        <option value="admin"    {{ $user->role === 'admin'    ? 'selected' : '' }}>👑 Admin</option>
                    </select>
                    @endif
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm">
                        Save Changes
                    </button>
                    <a href="{{ route('users.index') }}"
                       class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Reset Password --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">Reset Password</h2>

            <form method="POST" action="{{ route('users.password', $user) }}" class="space-y-4">
                @csrf @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password *</label>
                    <input type="password" name="password" required
                           placeholder="Minimum 8 characters"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <button type="submit"
                        onclick="return confirm('Reset password for {{ $user->name }}?')"
                        class="px-6 py-2.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 font-medium text-sm">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
