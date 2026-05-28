{{-- Offline Sync Dashboard
    Shows the status of all pending/failed invoice fiscalizations.
    Accessible from the status bar when there are pending invoices.
    This is the cashier's view of "how many sales are waiting to reach KRA".
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">KRA Sync Status</h1>
            <div id="connectivity-badge"
                 class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium">
                <!-- Updated by JavaScript based on connectivity state -->
            </div>
        </div>
    </x-slot>

    <div class="p-6 space-y-6" x-data="syncDashboard()">

        {{-- ─── Connectivity Status ────────────────────────────────────── --}}
        <div class="rounded-xl p-5 border-2 transition-all"
             :class="isOnline
                 ? 'bg-green-50 border-green-200'
                 : 'bg-yellow-50 border-yellow-300'">
            <div class="flex items-center gap-3">
                <div class="text-3xl" x-text="isOnline ? '🟢' : '🔴'"></div>
                <div>
                    <div class="font-semibold text-gray-800"
                         x-text="isOnline ? 'Online — Connected to KRA' : 'Offline — Working locally'">
                    </div>
                    <div class="text-sm text-gray-500 mt-0.5"
                         x-text="isOnline
                             ? 'Invoices are being submitted to KRA automatically'
                             : 'Sales are being saved locally. Will sync when internet returns.'">
                    </div>
                </div>
                <button x-show="isOnline && pendingCount > 0"
                        @click="triggerSync()"
                        class="ml-auto px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 font-medium">
                    🔄 Sync Now
                </button>
            </div>
        </div>

        {{-- ─── Status Cards ────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Pending Sync</div>
                <div class="text-3xl font-bold" :class="pendingCount > 0 ? 'text-yellow-600' : 'text-gray-300'"
                     x-text="pendingCount">
                    {{ $diagnostic['status']['pending'] }}
                </div>
                <div class="text-xs text-gray-400 mt-1">Awaiting KRA</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Fiscalized Today</div>
                <div class="text-3xl font-bold text-green-600">{{ $diagnostic['status']['fiscalized_today'] }}</div>
                <div class="text-xs text-gray-400 mt-1">Confirmed by KRA</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Failed</div>
                <div class="text-3xl font-bold {{ $diagnostic['status']['failed'] > 0 ? 'text-red-600' : 'text-gray-300' }}">
                    {{ $diagnostic['status']['failed'] }}
                </div>
                <div class="text-xs text-gray-400 mt-1">Needs attention</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Queue Jobs</div>
                <div class="text-3xl font-bold text-blue-600">{{ $diagnostic['queue_jobs'] }}</div>
                <div class="text-xs text-gray-400 mt-1">In SQLite queue</div>
            </div>
        </div>

        {{-- ─── How Offline Sync Works ─────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">How Offline Mode Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl mb-2">🛒</div>
                    <div class="font-semibold">1. Sale happens</div>
                    <div class="text-gray-500 mt-1 text-xs">Checkout completes instantly. Receipt printed. Stock updated.</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl mb-2">💾</div>
                    <div class="font-semibold">2. Saved locally</div>
                    <div class="text-gray-500 mt-1 text-xs">Invoice queued in SQLite. Survives power cuts & crashes.</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="text-2xl mb-2">📶</div>
                    <div class="font-semibold">3. Internet returns</div>
                    <div class="text-gray-500 mt-1 text-xs">Queue worker wakes up. Submits to KRA with retry logic.</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="text-2xl mb-2">✅</div>
                    <div class="font-semibold">4. KRA confirms</div>
                    <div class="text-gray-500 mt-1 text-xs">Receipt gets KRA number. Fiscalization complete.</div>
                </div>
            </div>
        </div>

        {{-- ─── Recovery Actions ────────────────────────────────────────── --}}
        @if($diagnostic['status']['failed'] > 0 || $diagnostic['status']['pending'] > 0)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">Recovery Actions</h2>
            <div class="flex flex-wrap gap-3">
                @if($diagnostic['status']['failed'] > 0)
                <form action="{{ route('sync.retry-all') }}" method="POST">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Re-queue all {{ $diagnostic['status']['failed'] }} failed invoice(s)?')"
                            class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                        🔄 Retry All Failed ({{ $diagnostic['status']['failed'] }})
                    </button>
                </form>
                @endif
                <form action="{{ route('sync.recover') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 font-medium">
                        🔧 Recover Orphaned Sales
                    </button>
                </form>
                <a href="{{ route('invoices.failed') }}"
                   class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                    View Failed Invoices →
                </a>
            </div>
        </div>
        @endif

        {{-- ─── System Info ─────────────────────────────────────────────── --}}
        <div class="bg-gray-900 rounded-xl p-5 text-xs font-mono text-gray-400">
            <div class="text-green-400 mb-2">// System Diagnostics</div>
            <div>DB Size: {{ $diagnostic['db_size_mb'] }} MB (SQLite)</div>
            <div>Queue Jobs: {{ $diagnostic['queue_jobs'] }} pending in jobs table</div>
            <div>Failed Jobs: {{ $diagnostic['failed_jobs'] }} in failed_jobs table</div>
            <div>SDK — Submitted: {{ $diagnostic['sdk_submitted'] }} | Pending: {{ $diagnostic['sdk_pending'] }} | Failed: {{ $diagnostic['sdk_failed'] }}</div>
            @if($diagnostic['status']['oldest_pending'])
            <div class="text-yellow-400 mt-1">Oldest pending: {{ $diagnostic['status']['oldest_invoice'] }} ({{ $diagnostic['status']['oldest_pending'] }})</div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
    function syncDashboard() {
        return {
            isOnline: false,
            pendingCount: {{ $diagnostic['status']['pending'] }},

            init() {
                // Get initial status from Electron
                if (window.electronAPI) {
                    window.electronAPI.getStatus().then(status => {
                        this.isOnline     = status.isOnline;
                        this.pendingCount = status.pendingCount;
                    });

                    // Listen for real-time updates from Electron
                    window.electronAPI.onConnectivityChange(({ isOnline }) => {
                        this.isOnline = isOnline;
                    });

                    window.electronAPI.onPendingUpdate(({ count }) => {
                        this.pendingCount = count;
                    });
                }
            },

            async triggerSync() {
                if (window.electronAPI) {
                    await window.electronAPI.triggerSync();
                }
            },
        }
    }
    </script>
    @endpush
</x-app-layout>
