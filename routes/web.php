<?php

use App\Http\Controllers\Admin\{
    DashboardController,
    OperatorController,
    RetailerController,
    SettingController,
    TransactionController
};
use App\Http\Controllers\Api\{
    DingConnectController,
    OperatorController as ApiOperatorController,
    PaymentWebhookController
};
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Retailer\{
    DashboardController as RetailerDashboardController,
    ProfileController as RetailerProfileController,
    RechargeController,
    TransactionController as RetailerTransactionController,
    WalletController
};
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==========================================================================
// LANDING PAGE & PUBLIC
// ==========================================================================
Route::get('/', [LandingController::class, 'index'])->name('home');

Route::get('/contact', fn() => Inertia::render('Landing/Contact/Index'))->name('contact');

// ==========================================================================
// AUTHENTICATION
// ==========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', fn() => Inertia::render('Auth/Register'))->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', fn() => Inertia::render('Auth/ForgotPassword'))->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', fn($token) => Inertia::render('Auth/ResetPassword', ['token' => $token]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ==========================================================================
// ADMIN ROUTES
// ==========================================================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Retailer Management
    Route::resource('retailers', RetailerController::class)->except(['show']);
    Route::get('/retailers/{retailer}', [RetailerController::class, 'show'])->name('retailers.show');
    Route::post('/retailers/{retailer}/approve', [RetailerController::class, 'approve'])->name('retailers.approve');
    Route::post('/retailers/{retailer}/block', [RetailerController::class, 'block'])->name('retailers.block');
    Route::post('/retailers/{retailer}/credit', [RetailerController::class, 'creditWallet'])->name('retailers.credit');
    Route::post('/retailers/{retailer}/kyc', [RetailerController::class, 'processKyc'])->name('retailers.kyc');
    Route::get('/retailers/export', [RetailerController::class, 'export'])->name('retailers.export');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');
    Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');

    // Operators
    Route::resource('operators', OperatorController::class);
    Route::post('operators/sync', [OperatorController::class, 'syncFromDing'])->name('operators.sync');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});

// ==========================================================================
// RETAILER ROUTES
// ==========================================================================
Route::prefix('retailer')->name('retailer.')->middleware(['auth', 'retailer'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [RetailerDashboardController::class, 'index'])->name('dashboard');

    // Wallet
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletController::class, 'initiateTopUp'])->name('wallet.topup');
    Route::post('/wallet/verify', [WalletController::class, 'verifyPayment'])->name('wallet.verify');
    Route::get('/wallet/ledger', [WalletController::class, 'ledger'])->name('wallet.ledger');

    // Recharge
    Route::get('/recharge', [RechargeController::class, 'index'])->name('recharge.index');
    Route::post('/recharge', [RechargeController::class, 'initiate'])->name('recharge.initiate');
    Route::get('/recharge/operators', [RechargeController::class, 'getOperators'])->name('recharge.operators');
    Route::get('/recharge/pricing', [RechargeController::class, 'getPricing'])->name('recharge.pricing');

    // Transactions
    Route::get('/transactions', [RetailerTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [RetailerTransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{transaction}/receipt', [RetailerTransactionController::class, 'receipt'])->name('transactions.receipt');

    // Profile
    Route::get('/profile', [RetailerProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [RetailerProfileController::class, 'update'])->name('profile.update');
});

// ==========================================================================
// API ROUTES (callbacks, webhooks, public endpoints)
// ==========================================================================
Route::prefix('api')->name('api.')->group(function () {
    Route::post('/ding/callback', [DingConnectController::class, 'callback'])->name('ding.callback');
    Route::post('/payment/webhook', [PaymentWebhookController::class, 'webhook'])->name('payment.webhook');
    Route::get('/operators', [ApiOperatorController::class, 'index'])->name('operators.index');
    Route::get('/countries', [ApiOperatorController::class, 'countries'])->name('countries.index');
});

// ==========================================================================
// BROADCASTING
// ==========================================================================
Route::get('/broadcasting/auth', function () {
    return Broadcast::auth(request()->user());
})->middleware('auth');

// ==========================================================================
// LEGACY REDIRECTS (for compatibility)
// ==========================================================================
Route::get('/admin', fn() => redirect('/admin/dashboard'))->middleware(['auth', 'admin']);
Route::get('/retailer', fn() => redirect('/retailer/dashboard'))->middleware(['auth', 'retailer']);

// ==========================================================================
// PROFILE (from Breeze scaffold)
// ==========================================================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

// DingConnect API test route (remove after testing)
Route::get('/test-ding', function (\App\Services\DingConnectService $ding) {
    \Log::info('=== DING TEST START ===');
    \Log::info('URL: ' . config('platform.dingconnect.base_url'));
    \Log::info('Key: ' . substr(config('platform.dingconnect.api_key'), 0, 8) . '...');
    \Log::info('CustomerID: ' . (config('platform.dingconnect.customer_id') ?: '(empty)'));

    try {
        $result = $ding->getCountries();
        \Log::info('Result:', $result);
        return response()->json([
            'url' => config('platform.dingconnect.base_url'),
            'has_customer_id' => !empty(config('platform.dingconnect.customer_id')),
            'result' => $result,
        ]);
    } catch (Exception $e) {
        \Log::error('Test error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->middleware(['auth', 'admin'])->name('test.ding');