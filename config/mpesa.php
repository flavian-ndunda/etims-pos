<?php
return [
    'sandbox'             => env('MPESA_SANDBOX', true),
    'consumer_key'        => env('MPESA_CONSUMER_KEY'),
    'consumer_secret'     => env('MPESA_CONSUMER_SECRET'),
    'shortcode'           => env('MPESA_SHORTCODE', '174379'),
    'passkey'             => env('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
    'callback_url'        => env('MPESA_CALLBACK_URL'),
    'result_url'          => env('MPESA_RESULT_URL'),
    'timeout_url'         => env('MPESA_TIMEOUT_URL'),
    'initiator_name'      => env('MPESA_INITIATOR_NAME'),
    'security_credential' => env('MPESA_SECURITY_CREDENTIAL'),
    'use_callback'        => env('MPESA_USE_CALLBACK', false),
    'polling_interval'    => env('MPESA_POLLING_INTERVAL', 3),
    'polling_timeout'     => env('MPESA_POLLING_TIMEOUT', 120),
];
