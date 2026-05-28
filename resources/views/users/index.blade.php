<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">👥 User Management</h1>
            <a href="{{ route('users.create') }}"
               class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                + Add User
            </a>
        </div>
    </x-slot>

    <div class="p-6">

        {{-- Role explanation --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                <div class="font-semibold text-purple-800 mb-1">👑 Admin</div>
                <div class="text-xs text-purple-600">Full access. Manages users, products, categories. Views all reports and invoices.</div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="font-semibold text-blue-800 mb-1">📊 Manager</div>
                <div class="text-xs text-blue-600">Views invoices, reports, M-Pesa, sync. Uses POS. Cannot manage users or products.</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="font-semibold text-green-800 mb-1">🛒 Cashier</div>
                <div class="text-xs text-green-600">POS terminal only. Can checkout and view receipts. No access to reports or settings.</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Name</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Email</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Role</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Joined</th>
                        <th class="text-right px-4 py-3 text-gray-600 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr class="border-b hover:bg-gray-50 {{ $user->id === auth()->id() ? 'bg-blue-50/30' : '' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $user->name }}</div>
                            @if($user->id === auth()->id())
                            <div class="text-xs text-blue-500">You</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                {{ $user->role === 'admin'   ? 'bg-purple-100 text-purple-700' :
                                   ($user->role === 'manager' ? 'bg-blue-100 text-blue-700' :
                                   'bg-green-100 text-green-700') }}">
                                {{ $user->roleLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('users.edit', $user) }}"
                               class="text-blue-600 hover:underline text-xs mr-3">Edit</a>

                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Delete {{ $user->name }}? This cannot be undone.')"
                                        class="text-red-500 hover:underline text-xs">
                                    Delete
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
