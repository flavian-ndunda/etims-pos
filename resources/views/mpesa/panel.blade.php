{{--
    M-Pesa Payment Panel
    Embedded within the POS checkout area via Alpine.js.
    Shows when cashier selects MPESA as payment type.

    Three sub-panels:
      1. STK Push     — send prompt to phone
      2. Manual Verify — enter transaction code
      3. Unclaimed     — pick from already-confirmed payments
--}}

<div x-data="mpesaPanel()" x-show="paymentType === 'MPESA'" class="mt-3 border border-green-200 rounded-xl overflow-hidden">

    {{-- Header --}}
    <div class="bg-green-50 px-4 py-3 border-b border-green-200 flex items-center gap-2">
        <span class="text-lg">📱</span>
        <span class="font-semibold text-green-800 text-sm">M-Pesa Payment</span>
        <span x-show="confirmedPayment" class="ml-auto text-xs bg-green-600 text-white px-2 py-0.5 rounded-full">✅ Confirmed</span>
    </div>

    {{-- Tab Navigation --}}
    <div class="flex border-b border-green-100 bg-white">
        <button @click="activeTab = 'stk'"
                :class="activeTab === 'stk' ? 'border-b-2 border-green-600 text-green-700 font-medium' : 'text-gray-500'"
                class="flex-1 text-xs py-2.5 px-3 hover:bg-green-50 transition-colors">
            📲 STK Push
        </button>
        <button @click="activeTab = 'manual'"
                :class="activeTab === 'manual' ? 'border-b-2 border-green-600 text-green-700 font-medium' : 'text-gray-500'"
                class="flex-1 text-xs py-2.5 px-3 hover:bg-green-50 transition-colors">
            🔍 Verify Code
        </button>
        <button @click="activeTab = 'unclaimed'; loadUnclaimed()"
                :class="activeTab === 'unclaimed' ? 'border-b-2 border-green-600 text-green-700 font-medium' : 'text-gray-500'"
                class="flex-1 text-xs py-2.5 px-3 hover:bg-green-50 transition-colors">
            📋 Unclaimed
        </button>
    </div>

    <div class="p-4 bg-white space-y-3">

        {{-- ═══ TAB 1: STK Push ════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'stk'">

            {{-- Confirmed state --}}
            <div x-show="confirmedPayment && confirmedPayment.type === 'stk'"
                 class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                <div class="font-semibold text-green-800">✅ Payment Confirmed</div>
                <div class="text-green-700 mt-1">
                    KES <span x-text="confirmedPayment?.amount?.toLocaleString()"></span> received
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    Code: <span x-text="confirmedPayment?.transaction_code" class="font-mono"></span>
                </div>
                <button @click="resetPayment()" class="text-xs text-red-500 underline mt-2">Use different payment</button>
            </div>

            {{-- Initiate form --}}
            <div x-show="!confirmedPayment">
                <div class="text-xs text-gray-500 mb-3">
                    Send a payment request directly to the customer's phone. They will receive an M-Pesa prompt and enter their PIN.
                </div>

                <div class="space-y-2">
                    <div>
                        <label class="text-xs text-gray-600 font-medium">Customer Phone *</label>
                        <div class="flex gap-1 mt-1">
                            <span class="px-2 py-2 bg-gray-100 border border-gray-300 rounded-l-lg text-xs text-gray-500">+254</span>
                            <input x-model="stkPhone"
                                   type="tel"
                                   placeholder="712 345 678"
                                   class="flex-1 border border-gray-300 rounded-r-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>

                    <div class="text-xs text-gray-500">
                        Amount: <strong class="text-gray-800">KES <span x-text="cartTotal.toFixed(2)"></span></strong>
                        (automatically set from cart total)
                    </div>

                    {{-- Status display during STK wait --}}
                    <div x-show="stkStatus" class="p-2 rounded-lg text-xs"
                         :class="{
                             'bg-yellow-50 text-yellow-700': stkStatus === 'awaiting',
                             'bg-green-50 text-green-700': stkStatus === 'completed',
                             'bg-red-50 text-red-700': stkStatus === 'failed'
                         }">
                        <div x-show="stkStatus === 'awaiting'" class="flex items-center gap-2">
                            <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Waiting for customer to enter PIN... (<span x-text="pollCountdown"></span>s)
                        </div>
                        <div x-show="stkStatus === 'completed'">✅ Payment confirmed!</div>
                        <div x-show="stkStatus === 'failed'" x-text="stkError"></div>
                    </div>

                    <button @click="initiateStkPush()"
                            :disabled="!stkPhone || stkStatus === 'awaiting' || loading"
                            class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                        <span x-show="!loading && stkStatus !== 'awaiting'">📲 Send Payment Request</span>
                        <span x-show="loading">Sending...</span>
                        <span x-show="stkStatus === 'awaiting'">⏳ Waiting for Customer...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ TAB 2: Manual Verification ════════════════════════════════ --}}
        <div x-show="activeTab === 'manual'">

            {{-- Confirmed state --}}
            <div x-show="confirmedPayment && confirmedPayment.type === 'manual'"
                 class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                <div class="font-semibold text-green-800">✅ Transaction Verified</div>
                <div class="text-green-700 mt-1">
                    <span x-text="confirmedPayment?.transaction_code" class="font-mono font-bold"></span>
                    — KES <span x-text="confirmedPayment?.amount?.toLocaleString()"></span>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    From: <span x-text="confirmedPayment?.phone"></span>
                </div>
                <button @click="resetPayment()" class="text-xs text-red-500 underline mt-2">Use different payment</button>
            </div>

            {{-- Verify form --}}
            <div x-show="!confirmedPayment">
                <div class="text-xs text-gray-500 mb-3">
                    Customer already paid? Enter the M-Pesa transaction code from their confirmation SMS
                    (e.g. <span class="font-mono bg-gray-100 px-1 rounded">RGH4K2X3L1</span>).
                    We verify it was paid to your shortcode and the amount matches.
                </div>

                <div class="space-y-2">
                    <div>
                        <label class="text-xs text-gray-600 font-medium">M-Pesa Transaction Code *</label>
                        <input x-model="manualCode"
                               type="text"
                               maxlength="20"
                               placeholder="e.g. RGH4K2X3L1"
                               @input="manualCode = manualCode.toUpperCase()"
                               class="w-full mt-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div x-show="verifyError" class="p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700" x-text="verifyError"></div>
                    <div x-show="verifySuccess" class="p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700" x-text="verifySuccess"></div>

                    <button @click="verifyManual()"
                            :disabled="!manualCode || loading"
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                        <span x-show="!loading">🔍 Verify Transaction</span>
                        <span x-show="loading">Verifying with Safaricom...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ TAB 3: Unclaimed Payments ════════════════════════════════ --}}
        <div x-show="activeTab === 'unclaimed'">
            <div class="text-xs text-gray-500 mb-3">
                Previously confirmed M-Pesa payments not yet linked to an invoice.
                Select one to use for this sale.
            </div>

            <div x-show="loadingUnclaimed" class="text-center py-4 text-gray-400 text-sm">Loading...</div>

            <div x-show="!loadingUnclaimed && unclaimedPayments.length === 0"
                 class="text-center py-4 text-gray-400 text-sm">
                No unclaimed M-Pesa payments found.
            </div>

            <div x-show="!loadingUnclaimed" class="space-y-2 max-h-48 overflow-y-auto">
                <template x-for="payment in unclaimedPayments" :key="payment.id">
                    <div class="flex items-center justify-between p-2.5 border border-gray-200 rounded-lg hover:border-green-400 hover:bg-green-50 cursor-pointer transition-all"
                         @click="selectUnclaimedPayment(payment)">
                        <div>
                            <div class="text-sm font-mono font-semibold" x-text="payment.transaction_code"></div>
                            <div class="text-xs text-gray-500" x-text="payment.phone_number + ' · ' + payment.paid_at"></div>
                            <div class="text-xs text-gray-400" x-text="payment.type"></div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-green-700" x-text="payment.amount_formatted"></div>
                            <div class="text-xs text-green-500"
                                 :class="Math.abs(payment.amount - cartTotal) <= 1 ? 'text-green-500' : 'text-orange-500'"
                                 x-text="Math.abs(payment.amount - cartTotal) <= 1 ? '✅ Exact amount' : '⚠️ Amount differs'">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

    {{-- Hidden input — carries the confirmed payment ID to the checkout form --}}
    <input type="hidden" name="mpesa_payment_id" :value="confirmedPayment?.payment_id">

</div>

<script>
function mpesaPanel() {
    return {
        activeTab:         'stk',
        stkPhone:          '',
        stkStatus:         null,  // null | 'awaiting' | 'completed' | 'failed'
        stkError:          '',
        manualCode:        '',
        verifyError:       '',
        verifySuccess:     '',
        loading:           false,
        loadingUnclaimed:  false,
        confirmedPayment:  null,   // { payment_id, transaction_code, amount, phone, type }
        unclaimedPayments: [],
        pollCountdown:     120,
        pollTimer:         null,

        get cartTotal() {
            // Access the parent Alpine scope's cart total
            return this.$root?.closest('[x-data]')?._x_dataStack?.[0]?.totals?.total ?? 0;
        },

        // ─── STK Push ──────────────────────────────────────────────────────

        async initiateStkPush() {
            this.loading   = true;
            this.stkStatus = null;
            this.stkError  = '';

            const response = await fetch('{{ route("mpesa.stk.initiate") }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body:    JSON.stringify({
                    phone:     '254' + this.stkPhone.replace(/\D/g, '').replace(/^0/, ''),
                    amount:    Math.ceil(this.cartTotal),
                    reference: 'POS-' + Date.now().toString().slice(-6),
                }),
            }).then(r => r.json());

            this.loading = false;

            if (response.success) {
                this.stkStatus = 'awaiting';
                this.startPolling(response.payment_id);
            } else {
                this.stkStatus = 'failed';
                this.stkError  = response.message;
            }
        },

        startPolling(paymentId) {
            this.pollCountdown = 120;
            this.pollTimer = setInterval(async () => {
                this.pollCountdown--;

                const status = await fetch(`/mpesa/stk/status/${paymentId}`)
                    .then(r => r.json());

                if (status.is_confirmed) {
                    clearInterval(this.pollTimer);
                    this.stkStatus = 'completed';
                    this.confirmedPayment = {
                        payment_id:       paymentId,
                        transaction_code: status.transaction_code,
                        amount:           status.amount,
                        phone:            this.stkPhone,
                        type:             'stk',
                    };
                } else if (status.is_failed || this.pollCountdown <= 0) {
                    clearInterval(this.pollTimer);
                    this.stkStatus = 'failed';
                    this.stkError  = status.is_failed
                        ? '❌ Customer cancelled or payment timed out.'
                        : '⏰ Payment request expired. Please try again.';
                }
            }, 3000); // Poll every 3 seconds
        },

        // ─── Manual Verification ───────────────────────────────────────────

        async verifyManual() {
            this.loading      = true;
            this.verifyError  = '';
            this.verifySuccess = '';

            const response = await fetch('{{ route("mpesa.verify") }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body:    JSON.stringify({
                    transaction_code: this.manualCode,
                    amount:           this.cartTotal,
                }),
            }).then(r => r.json());

            this.loading = false;

            if (response.success) {
                this.verifySuccess = response.message;
                this.confirmedPayment = {
                    payment_id:       response.payment_id,
                    transaction_code: response.transaction_code,
                    amount:           response.amount,
                    phone:            response.phone_number,
                    type:             'manual',
                };
            } else {
                this.verifyError = response.message;
            }
        },

        // ─── Unclaimed Payments ────────────────────────────────────────────

        async loadUnclaimed() {
            this.loadingUnclaimed = true;
            const data = await fetch('{{ route("mpesa.unclaimed") }}').then(r => r.json());
            this.unclaimedPayments = data.payments ?? [];
            this.loadingUnclaimed  = false;
        },

        selectUnclaimedPayment(payment) {
            this.confirmedPayment = {
                payment_id:       payment.id,
                transaction_code: payment.transaction_code,
                amount:           payment.amount,
                phone:            payment.phone_number,
                type:             'unclaimed',
            };
        },

        // ─── Reset ────────────────────────────────────────────────────────

        resetPayment() {
            clearInterval(this.pollTimer);
            this.confirmedPayment = null;
            this.stkStatus        = null;
            this.stkError         = '';
            this.verifyError      = '';
            this.verifySuccess    = '';
            this.manualCode       = '';
        },
    }
}
</script>
