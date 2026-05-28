<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Mpesa\MpesaCallbackController;
use App\Http\Controllers\Mpesa\MpesaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/ping', fn() => response()->json(['status' => 'ok']));

// Auth
require __DIR__ . '/auth.php';

Route::middleware(['auth'])->group(function () {

    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS Terminal
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{sale}', [ReceiptController::class, 'show'])->name('receipt');
        Route::get('/receipt/{sale}/print', [ReceiptController::class, 'print'])->name('receipt.print');
        Route::post('/cart/add', [PosController::class, 'addToCart'])->name('cart.add');
        Route::patch('/cart/update', [PosController::class, 'updateCart'])->name('cart.update');
        Route::delete('/cart/remove', [PosController::class, 'removeFromCart'])->name('cart.remove');
        Route::delete('/cart/clear', [PosController::class, 'clearCart'])->name('cart.clear');
    });

    // Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/failed', [InvoiceController::class, 'failed'])->name('failed');
        Route::get('/{sale}', [InvoiceController::class, 'show'])->name('show');
        Route::post('/{sale}/retry', [InvoiceController::class, 'retry'])->name('retry');
    });

    // Sync Dashboard (offline mode)
    Route::prefix('sync')->name('sync.')->group(function () {
        Route::get('/', [SyncController::class, 'dashboard'])->name('dashboard');
        Route::post('/retry-all', [SyncController::class, 'retryAll'])->name('retry-all');
        Route::post('/recover', [SyncController::class, 'recover'])->name('recover');
    });
    
    // Product Categories
Route::get('/products/categories', [ProductController::class, 'categories'])->name('products.categories');
Route::post('/products/categories', [ProductController::class, 'storeCategory'])->name('products.categories.store');
Route::patch('/products/categories/{category}/toggle', [ProductController::class, 'toggleCategory'])->name('products.categories.toggle');

    // Products
    Route::resource('products', ProductController::class)
         ->only(['index', 'create', 'store', 'edit', 'update']);

    // M-Pesa
    Route::prefix('mpesa')->name('mpesa.')->group(function () {
        Route::get('/', [MpesaController::class, 'index'])->name('index');
        Route::post('/stk/initiate', [MpesaController::class, 'initiateStk'])->name('stk.initiate');
        Route::get('/stk/status/{payment}', [MpesaController::class, 'stkStatus'])->name('stk.status');
        Route::post('/verify', [MpesaController::class, 'verify'])->name('verify');
        Route::get('/unclaimed', [MpesaController::class, 'unclaimed'])->name('unclaimed');
    });
});

// Safaricom callbacks — no CSRF, no auth
Route::prefix('api')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::get('/pending-count', [SyncController::class, 'pendingCount'])->name('api.pending-count');
    Route::post('/mpesa/callback', [MpesaCallbackController::class, 'stkCallback'])->name('mpesa.callback');
    Route::post('/mpesa/result', [MpesaCallbackController::class, 'transactionResult'])->name('mpesa.result');
});
