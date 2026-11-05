<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutResultController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

    // Cart routes
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/sync', [CartController::class, 'sync'])->name('cart.sync');
    Route::post('/cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{item}', [CartItemController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');

    // Checkout routes
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');
    Route::get('/checkout/success', [CheckoutResultController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutResultController::class, 'cancel'])->name('checkout.cancel');
    Route::post('/checkout/retry/{order:public_id}', [CheckoutResultController::class, 'retry'])->name('checkout.retry');
    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

    // Favourites routes
    Route::get('/favourites', [FavouriteController::class, 'index'])->name('favourites.index');
    Route::post('/favourites/sync', [FavouriteController::class, 'sync'])->name('favourites.sync');
    Route::post('/favourites/{product}', [FavouriteController::class, 'toggle'])->name('favourites.toggle');

    // Customer Authentication routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'createLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'storeLogin']);
        Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'storeRegister']);
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

        // Customer Account routes
        Route::get('/account/profile', [AccountController::class, 'edit'])->name('account.profile');
        Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');

        // Customer Order History routes
        Route::get('/account/orders', [OrderController::class, 'index'])->name('account.orders.index');
        Route::get('/account/orders/{order:public_id}', [OrderController::class, 'show'])->name('account.orders.show');
    });
});
