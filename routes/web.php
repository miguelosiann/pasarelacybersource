<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payment\CheckoutController;
use App\Http\Controllers\Payment\ChallengeController;
use App\Http\Controllers\Payment\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// ===== PAYMENT CHALLENGE CALLBACK (PÚBLICO - Sin autenticación) =====
// Esta ruta NO requiere autenticación porque viene de un iframe externo de CardinalCommerce
Route::post('/payment/challenge/callback', [CheckoutController::class, 'handleChallengeCallback'])->name('payment.challenge.callback');

// ===== PAYMENT GATEWAY ROUTES =====
// Payment Routes (CyberSource)
Route::prefix('payment')->name('payment.')->group(function () {
    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'showForm'])->name('checkout');
    Route::post('/process', [CheckoutController::class, 'processPayment'])->name('process');
    Route::post('/continue-after-collection', [CheckoutController::class, 'continueAfterCollection'])->name('continue-after-collection');
    Route::get('/processing', [CheckoutController::class, 'processing'])->name('processing');
    
    // Results
    Route::get('/success/{payment?}', [PaymentController::class, 'success'])->name('success');
    Route::get('/failed', [PaymentController::class, 'failed'])->name('failed');
    
    // Payment History & Details
    Route::get('/history', [PaymentController::class, 'history'])->name('history');
    Route::get('/show/{payment}', [PaymentController::class, 'show'])->name('show');
    
    // 3DS Challenge Authorize (deprecated - usar callback directo)
    Route::post('/challenge/authorize', [ChallengeController::class, 'processAuthorizationAfterChallenge'])->name('challenge.authorize');
    
    // DEBUG Routes - Step by Step Execution
    Route::prefix('debug')->name('debug.')->group(function () {
        Route::get('/', [CheckoutController::class, 'showDebug'])->name('index');
        Route::post('/save-form', [CheckoutController::class, 'saveFormData'])->name('save-form');
        Route::post('/step1', [CheckoutController::class, 'debugStep1'])->name('step1');
        Route::post('/step2', [CheckoutController::class, 'debugStep2'])->name('step2');
        Route::post('/step3', [CheckoutController::class, 'debugStep3'])->name('step3');
        Route::post('/step4', [CheckoutController::class, 'debugStep4'])->name('step4');
        Route::post('/step5', [CheckoutController::class, 'debugStep5'])->name('step5');
        Route::post('/step5_5a', [CheckoutController::class, 'debugStep5_5a'])->name('step5_5a');
        Route::post('/step5_5b', [CheckoutController::class, 'debugStep5_5b'])->name('step5_5b');
    });
});
