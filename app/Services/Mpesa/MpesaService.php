<?php

declare(strict_types=1);

namespace App\Services\Mpesa;

use App\Models\MpesaPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * MpesaService
 *
 * Handles all M-Pesa interactions with the Safaricom Daraja API.
 *
 * Supports two payment flows:
 *
 * ─── Flow 1: STK Push ──────────────────────────────────────────────────────
 * 1. Cashier enters customer phone + amount
 * 2. We call Daraja /stkpush/v1/processrequest
 * 3. Safaricom sends a payment prompt to the customer's phone
 * 4. Customer enters their M-Pesa PIN
 * 5. Safaricom sends a callback to /api/mpesa/callback (or we poll)
 * 6. We mark the MpesaPayment as 'completed'
 * 7. Cashier proceeds to checkout → payment is claimed
 *
 * ─── Flow 2: Manual Verification ──────────────────────────────────────────
 * Customer paid via M-Pesa independently (e.g. sent to Till/Paybill directly).
 * They show the cashier their M-Pesa confirmation SMS with a transaction code.
 * 1. Cashier enters the transaction code (e.g. RGH4K2X3L1)
 * 2. We call Daraja /query/v1/query to get transaction details
 * 3. We verify: amount matches cart total, recipient is our shortcode,
 *    and the payment isn't already claimed for another invoice
 * 4. We mark the MpesaPayment as 'verified'
 * 5. Cashier proceeds to checkout → payment is claimed
 *
 * ─── Offline Behavior ─────────────────────────────────────────────────────
 * STK Push requires internet (it's a real-time Safaricom API call).
 * Manual verification also requires internet to query Safaricom.
 * If offline, only Cash/Credit payment types are available.
 * The POS UI disables the M-Pesa payment options when offline.
 */
class MpesaService
{
private string $consumerKey;
private string $consumerSecret;
private string $shortcode;
private string $passkey;
private ?string $callbackUrl;
private bool $sandbox;
private string $baseUrl;

public function __construct()
{
    $this->consumerKey    = (string) config('mpesa.consumer_key', '');
    $this->consumerSecret = (string) config('mpesa.consumer_secret', '');
    $this->shortcode      = (string) config('mpesa.shortcode', '174379');
    $this->passkey        = (string) config('mpesa.passkey', '');
    $this->callbackUrl    = config('mpesa.callback_url') ?: null;
    $this->sandbox        = (bool) config('mpesa.sandbox', true);
    $this->baseUrl        = $this->sandbox
        ? 'https://sandbox.safaricom.co.ke'
        : 'https://api.safaricom.co.ke';
} // =========================================================================
    // STK Push
    // =========================================================================

    /**
     * Initiate an STK push to the customer's phone.
     *
     * Creates a MpesaPayment record with status 'awaiting_confirmation'
     * and returns it. The caller should poll checkStkStatus() until
     * the payment is confirmed or times out.
     *
     * @throws \RuntimeException On Safaricom API failure
     */
    public function initiateStkPush(
        string $phone,
        float $amount,
        string $accountReference,
        string $description,
        int $cashierId,
    ): MpesaPayment {
        $phone     = $this->normalizePhone($phone);
        $amount    = (int) ceil($amount); // M-Pesa only accepts whole KES
        $token     = $this->getAccessToken();
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($this->shortcode . $this->passkey . $timestamp);

        Log::info('[Mpesa] Initiating STK push', [
            'phone'     => $phone,
            'amount'    => $amount,
            'reference' => $accountReference,
        ]);

        $response = Http::withToken($token)
            ->timeout(15)
            ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", [
                'BusinessShortCode' => $this->shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => $amount,
                'PartyA'            => $phone,
                'PartyB'            => $this->shortcode,
                'PhoneNumber'       => $phone,
                'CallBackURL'       => $this->callbackUrl,
                'AccountReference'  => $accountReference,
                'TransactionDesc'   => $description,
            ]);

        $body = $response->json();

        Log::info('[Mpesa] STK push response', ['body' => $body]);

        if (!$response->successful() || ($body['ResponseCode'] ?? '') !== '0') {
            throw new \RuntimeException(
                'M-Pesa STK push failed: ' . ($body['errorMessage'] ?? $body['ResponseDescription'] ?? 'Unknown error')
            );
        }

        return MpesaPayment::create([
            'type'                => 'stk_push',
            'status'              => 'awaiting_confirmation',
            'phone_number'        => $phone,
            'amount'              => $amount,
            'merchant_request_id' => $body['MerchantRequestID'],
            'checkout_request_id' => $body['CheckoutRequestID'],
            'cashier_id'          => $cashierId,
        ]);
    }

    /**
     * Poll Safaricom to check the status of a pending STK push.
     *
     * Call this every 3 seconds after initiating an STK push.
     * Returns the updated MpesaPayment with status:
     *   'awaiting_confirmation' → still waiting
     *   'completed'             → customer paid ✅
     *   'failed'                → customer cancelled or timeout ❌
     *
     * @throws \RuntimeException On API error
     */
    public function checkStkStatus(MpesaPayment $payment): MpesaPayment
    {
        if (!$payment->isPending()) {
            return $payment; // Already resolved — no need to poll
        }

        $token     = $this->getAccessToken();
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $response = Http::withToken($token)
            ->timeout(10)
            ->post("{$this->baseUrl}/mpesa/stkpushquery/v1/query", [
                'BusinessShortCode' => $this->shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'CheckoutRequestID' => $payment->checkout_request_id,
            ]);

        $body = $response->json();

        Log::debug('[Mpesa] STK status poll', [
            'checkout_request_id' => $payment->checkout_request_id,
            'response'            => $body,
        ]);

        $resultCode = (string) ($body['ResultCode'] ?? '');

        if ($resultCode === '0') {
            // Success — extract transaction details
            $payment->update([
                'status'           => 'completed',
                'result_code'      => $resultCode,
                'result_desc'      => $body['ResultDesc'] ?? 'The service request is processed successfully.',
                'transaction_code' => $this->extractFromCallback($body, 'MpesaReceiptNumber'),
                'paid_at'          => now(),
                'raw_callback'     => $body,
            ]);
        } elseif ($resultCode !== '' && $resultCode !== '1032') {
            // Non-zero result code (except 1032 = still processing) means failure
            $payment->update([
                'status'      => 'failed',
                'result_code' => $resultCode,
                'result_desc' => $body['ResultDesc'] ?? 'Transaction failed',
                'raw_callback' => $body,
            ]);
        }
        // ResultCode 1032 = still processing → keep status as 'awaiting_confirmation'

        return $payment->fresh();
    }

    // =========================================================================
    // Manual Verification
    // =========================================================================

    /**
     * Verify a manually entered M-Pesa transaction code.
     *
     * The customer shows their M-Pesa SMS to the cashier.
     * The cashier types the transaction code (e.g. RGH4K2X3L1).
     *
     * This method:
     *   1. Checks the code hasn't already been claimed for another invoice
     *   2. Queries Safaricom to get transaction details
     *   3. Verifies the amount and recipient match
     *   4. Creates and returns a verified MpesaPayment
     *
     * @throws \RuntimeException On validation failure or API error
     */
    public function verifyManualTransaction(
        string $transactionCode,
        float $expectedAmount,
        int $cashierId,
    ): MpesaPayment {
        $code = strtoupper(trim($transactionCode));

        // Guard 1: Check if this code is already claimed for another invoice
        $existing = MpesaPayment::where('transaction_code', $code)->first();

        if ($existing) {
            if ($existing->isClaimed()) {
                throw new \RuntimeException(
                    "Transaction {$code} has already been applied to invoice #{$existing->sale?->invoice_number}. " .
                    'Each M-Pesa payment can only be used once.'
                );
            }

            if ($existing->isConfirmed()) {
                // Already verified and unclaimed — return it directly
                return $existing;
            }
        }

        // Guard 2: Query Safaricom for the transaction details
        $details = $this->queryTransaction($code);

        // Guard 3: Verify amount (allow ±1 KES rounding tolerance)
        $paidAmount = (float) ($details['TransactionAmount'] ?? 0);
        if (abs($paidAmount - $expectedAmount) > 1.0) {
            throw new \RuntimeException(
                "Amount mismatch: transaction {$code} shows KES " . number_format($paidAmount, 2) .
                " but the cart total is KES " . number_format($expectedAmount, 2) . '. ' .
                'Please ensure the customer paid the exact amount.'
            );
        }

        // Guard 4: Verify recipient is our shortcode / paybill
        $recipient = (string) ($details['ReceiverPartyPublicName'] ?? '');
        if ($this->shortcode && !str_contains($recipient, $this->shortcode)) {
            throw new \RuntimeException(
                "Transaction {$code} was paid to '{$recipient}', not to your M-Pesa shortcode ({$this->shortcode}). " .
                'Verify the customer paid the correct number.'
            );
        }

        Log::info('[Mpesa] Manual transaction verified', [
            'code'   => $code,
            'amount' => $paidAmount,
            'phone'  => $details['MSISDN'] ?? 'unknown',
        ]);

        return MpesaPayment::create([
            'type'             => 'manual_verification',
            'status'           => 'verified',
            'phone_number'     => $this->normalizePhone($details['MSISDN'] ?? ''),
            'amount'           => $paidAmount,
            'transaction_code' => $code,
            'result_code'      => '0',
            'result_desc'      => 'Manually verified by cashier',
            'raw_callback'     => $details,
            'claimed'          => false,
            'cashier_id'       => $cashierId,
            'paid_at'          => now(),
        ]);
    }

    /**
     * Get all unclaimed M-Pesa payments (confirmed but not yet applied to an invoice).
     *
     * Used to show the cashier a list of payments they can apply at checkout.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, MpesaPayment>
     */
    public function unclaimedPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return MpesaPayment::unclaimed()
            ->orderByDesc('paid_at')
            ->limit(20)
            ->get();
    }

    // =========================================================================
    // OAuth Token
    // =========================================================================

    /**
     * Get a Safaricom Daraja API access token.
     *
     * Tokens are cached for 55 minutes (they expire at 60 minutes).
     * This avoids re-authenticating on every API call.
     *
     * @throws \RuntimeException On auth failure
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'mpesa_access_token_' . md5($this->consumerKey);

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->timeout(10)
                ->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

            $body = $response->json();

            if (!$response->successful() || !isset($body['access_token'])) {
                throw new \RuntimeException(
                    'Failed to authenticate with Safaricom Daraja: ' .
                    ($body['errorMessage'] ?? 'Unknown error')
                );
            }

            Log::debug('[Mpesa] Access token acquired');

            return $body['access_token'];
        });
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Query Safaricom for transaction details by transaction code.
     *
     * Uses the Daraja Transaction Status API.
     *
     * @return array<string, mixed>
     * @throws \RuntimeException On API failure
     */
    private function queryTransaction(string $transactionCode): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->timeout(15)
            ->post("{$this->baseUrl}/mpesa/transactionstatus/v1/query", [
                'Initiator'          => config('mpesa.initiator_name'),
                'SecurityCredential' => config('mpesa.security_credential'),
                'CommandID'          => 'TransactionStatusQuery',
                'TransactionID'      => $transactionCode,
                'PartyA'             => $this->shortcode,
                'IdentifierType'     => '4',
                'ResultURL'          => config('mpesa.result_url'),
                'QueueTimeOutURL'    => config('mpesa.timeout_url'),
                'Remarks'            => 'Transaction verification',
                'Occasion'           => 'POS verification',
            ]);

        $body = $response->json();

        Log::info('[Mpesa] Transaction query response', ['code' => $transactionCode, 'body' => $body]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Safaricom query failed for {$transactionCode}: " .
                ($body['errorMessage'] ?? $response->status())
            );
        }

        // The actual result comes via callback, but for synchronous verification
        // the sandbox returns it directly. In production, this requires a callback URL.
        // We return what we have — the caller handles partial data gracefully.
        return array_merge($body, [
            'TransactionCode' => $transactionCode,
        ]);
    }

    /**
     * Normalize a phone number to the 254XXXXXXXXX format Safaricom expects.
     */
    private function normalizePhone(string $phone): string
    {
        // Strip spaces and special characters
        $phone = preg_replace('/\D/', '', $phone);

        // Handle formats: 07XXXXXXXX → 2547XXXXXXXX
        if (str_starts_with($phone, '07') || str_starts_with($phone, '01')) {
            $phone = '254' . substr($phone, 1);
        }

        // Handle +254XXXXXXXXX
        if (str_starts_with($phone, '+254')) {
            $phone = substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Extract a field from a Safaricom callback's CallbackMetadata.
     */
    private function extractFromCallback(array $body, string $field): ?string
    {
        $metadata = $body['CallbackMetadata']['Item'] ?? [];
        foreach ($metadata as $item) {
            if (($item['Name'] ?? '') === $field) {
                return (string) ($item['Value'] ?? '');
            }
        }
        return null;
    }
}
