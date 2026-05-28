<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Mpesa\MpesaCallbackController;
use App\Http\Controllers\Mpesa\MpesaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Health check — no auth required
Route::get('/ping', fn() => response()->json(['status' => 'ok']));

// Auth routes
require __DIR__ . '/auth.php';

// ─── All authenticated routes ──────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::redirect('/', '/dashboard');

    // Dashboard — all roles
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ─── POS Terminal — cashier, manager, admin ────────────────────────────
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/',                    [PosController::class, 'index'])->name('index');
        Route::post('/checkout',           [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{sale}',      [ReceiptController::class, 'show'])->name('receipt');
        Route::get('/receipt/{sale}/print',[ReceiptController::class, 'print'])->name('receipt.print');
        Route::post('/cart/add',           [PosController::class, 'addToCart'])->name('cart.add');
        Route::patch('/cart/update',       [PosController::class, 'updateCart'])->name('cart.update');
        Route::delete('/cart/remove',      [PosController::class, 'removeFromCart'])->name('cart.remove');
        Route::delete('/cart/clear',       [PosController::class, 'clearCart'])->name('cart.clear');
    });

    // ─── Invoices — manager and admin only ────────────────────────────────
    Route::prefix('invoices')->name('invoices.')->middleware('role:manager')->group(function () {
        Route::get('/',              [InvoiceController::class, 'index'])->name('index');
        Route::get('/failed',        [InvoiceController::class, 'failed'])->name('failed');
        Route::get('/{sale}',        [InvoiceController::class, 'show'])->name('show');
        Route::post('/{sale}/retry', [InvoiceController::class, 'retry'])->name('retry');
    });

    // ─── Sync Dashboard — manager and admin only ──────────────────────────
    Route::prefix('sync')->name('sync.')->middleware('role:manager')->group(function () {
        Route::get('/',           [SyncController::class, 'dashboard'])->name('dashboard');
        Route::post('/retry-all', [SyncController::class, 'retryAll'])->name('retry-all');
        Route::post('/recover',   [SyncController::class, 'recover'])->name('recover');
    });

    // ─── M-Pesa — manager and admin only ──────────────────────────────────
    Route::prefix('mpesa')->name('mpesa.')->middleware('role:manager')->group(function () {
        Route::get('/',                       [MpesaController::class, 'index'])->name('index');
        Route::post('/stk/initiate',          [MpesaController::class, 'initiateStk'])->name('stk.initiate');
        Route::get('/stk/status/{payment}',   [MpesaController::class, 'stkStatus'])->name('stk.status');
        Route::post('/verify',                [MpesaController::class, 'verify'])->name('verify');
        Route::get('/unclaimed',              [MpesaController::class, 'unclaimed'])->name('unclaimed');
    });

    // ─── Products & Categories — admin only ───────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/products/categories',                    [ProductController::class, 'categories'])->name('products.categories');
        Route::post('/products/categories',                   [ProductController::class, 'storeCategory'])->name('products.categories.store');
        Route::patch('/products/categories/{category}/toggle',[ProductController::class, 'toggleCategory'])->name('products.categories.toggle');

        Route::resource('products', ProductController::class)
             ->only(['index', 'create', 'store', 'edit', 'update']);
    });

    // ─── User Management — admin only ─────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/users',                      [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create',               [UserController::class, 'create'])->name('users.create');
        Route::post('/users',                     [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit',          [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}',               [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/password',    [UserController::class, 'resetPassword'])->name('users.password');
        Route::delete('/users/{user}',            [UserController::class, 'destroy'])->name('users.destroy');
    });
});

// ─── Safaricom Callbacks — no CSRF, no auth ───────────────────────────────
Route::prefix('api')
     ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
     ->group(function () {
         Route::get('/pending-count',      [SyncController::class, 'pendingCount'])->name('api.pending-count');
         Route::post('/mpesa/callback',    [MpesaCallbackController::class, 'stkCallback'])->name('mpesa.callback');
         Route::post('/mpesa/result',      [MpesaCallbackController::class, 'transactionResult'])->name('mpesa.result');
     });
