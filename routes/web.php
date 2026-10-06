<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthorizationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConsumptionController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\IngredientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\TaxConfigurationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoidController;
use App\Http\Controllers\WasteController;
use Illuminate\Support\Facades\Route;

// ── Public landing ──────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));

// ── Auth routes (Breeze) ────────────────────────────────────────────────────
require __DIR__.'/auth.php';

// ── All authenticated routes ────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // ── Profile ──────────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Dashboard ────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── POS ──────────────────────────────────────────────────────────────
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/store', [PosController::class, 'store'])->name('store');
        Route::post('/save', [PosController::class, 'hold'])->name('hold');
        Route::get('/saved-orders', [PosController::class, 'heldOrders'])->name('held-orders');
        Route::post('/saved-orders/{order}/resume', [PosController::class, 'resumeHeld'])->name('resume-held');
        Route::patch('/saved-orders/{order}/pin', [PosController::class, 'togglePin'])->name('toggle-pin');
        Route::delete('/saved-orders/{order}', [PosController::class, 'discardHeld'])->name('discard-held');
        Route::get('/products', [PosController::class, 'products'])->name('products');       // AJAX
        Route::get('/products/{product}/sizes', [PosController::class, 'sizes'])->name('sizes');           // AJAX
        Route::get('/order-success/{order}', [PosController::class, 'success'])->name('success');
    });

    // ── Cashier Shifts ──────────────────────────────────────────────────
    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/current', [ShiftController::class, 'current'])->name('current');
        Route::post('/start', [ShiftController::class, 'start'])->name('start');
        Route::post('/preview-end', [ShiftController::class, 'previewEnd'])->name('preview-end');
        Route::post('/end', [ShiftController::class, 'end'])->name('end');
    });

    // ── Orders ───────────────────────────────────────────────────────────
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/{order}/payments', [OrderController::class, 'recordPayment'])->name('payments.store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/receipt', [OrderController::class, 'receipt'])->name('receipt');
    });

    // ── Notifications ────────────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::patch('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::patch('/{notification}/resolve', [NotificationController::class, 'resolve'])->name('resolve');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('markAllRead');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unreadCount'); // AJAX
    });

    // ── Authorization modal endpoint (for refunds / cancellations / adjustments) ──
    Route::post('/authorize', [AuthorizationController::class, 'verify'])->name('authorize');



    // ── Refunds History List (Owner, Manager) ───────────────────────────
    Route::middleware('role:owner,manager')->group(function () {
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    });

    // ── Voids History List (Owner, Manager) ───────────────────────────────
    Route::middleware('role:owner,manager')->group(function () {
        Route::get('/voids', [VoidController::class, 'index'])->name('voids.index');
    });

    // Refunds & Cancellations Actions (Authorized by manager credentials in controller)
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::post('/order/{order}/refund', [RefundController::class, 'refund'])->name('refund');
        Route::post('/order/{order}/cancel', [RefundController::class, 'cancel'])->name('cancel');
    });

    // Void Actions (Authorized by manager or owner credentials in controller)
    Route::post('/orders/{order}/void', [VoidController::class, 'voidOrder'])->name('orders.void');
    Route::post('/orders/{order}/items/{item}/void', [VoidController::class, 'voidItem'])->name('orders.items.void');

    // ── Manager / Owner routes ───────────────────────────────────────────
    Route::middleware('role:owner,manager')->group(function () {

        // Categories
        Route::resource('categories', CategoryController::class)->except(['show']);

        // Products
        Route::resource('products', ProductController::class)->except(['show']);
        Route::patch('/products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
        Route::delete('/products/{product}/sizes/{size}', [ProductController::class, 'destroySize'])->name('products.sizes.destroy');

        // Recipes
        Route::prefix('recipes')->name('recipes.')->group(function () {
            Route::get('/', [RecipeController::class, 'index'])->name('index');
            Route::get('/{productSize}/edit', [RecipeController::class, 'edit'])->name('edit');
            Route::put('/{productSize}', [RecipeController::class, 'update'])->name('update');
        });

        // Ingredients
        Route::resource('ingredients', IngredientController::class)->except(['show']);
        Route::patch('/ingredients/{ingredient}/toggle', [IngredientController::class, 'toggle'])->name('ingredients.toggle');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('/grab', [ReportController::class, 'grab'])->name('grab');
            Route::get('/shifts', [ReportController::class, 'shifts'])->name('shifts');
            Route::get('/shifts/{shift}', [ReportController::class, 'shiftDetail'])->name('shifts.show');
            Route::post('/shifts/{shift}/review', [ShiftController::class, 'review'])->name('shifts.review');
            Route::post('/shifts/{shift}/close', [ShiftController::class, 'closeOther'])->name('shifts.close');
            Route::post('/shifts/{shift}/adjustments', [ShiftController::class, 'adjustment'])->name('shifts.adjustments.store');
        });

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Tax Configuration
        Route::get('/settings/tax', [TaxConfigurationController::class, 'edit'])->name('settings.tax.edit');
        Route::put('/settings/tax', [TaxConfigurationController::class, 'update'])->name('settings.tax.update');
        Route::patch('/settings/tax/archive', [TaxConfigurationController::class, 'archive'])->name('settings.tax.archive');
        Route::patch('/settings/tax/unarchive', [TaxConfigurationController::class, 'unarchive'])->name('settings.tax.unarchive');
    });

    // ── Manager / Owner inventory routes ──────────────────────────────────
    Route::middleware('role:owner,manager')->group(function () {

        // Inventory overview
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions');
        });

        // Stock-In
        Route::prefix('inventory/stock-in')->name('stock-in.')->group(function () {
            Route::get('/', [StockInController::class, 'index'])->name('index');
            Route::post('/', [StockInController::class, 'store'])->name('store');
        });

        // Waste / Spoilage
        Route::prefix('inventory/waste')->name('waste.')->group(function () {
            Route::get('/', [WasteController::class, 'index'])->name('index');
            Route::post('/', [WasteController::class, 'store'])->name('store');
        });

        // Stock Adjustments
        Route::prefix('inventory/adjustments')->name('adjustments.')->group(function () {
            Route::get('/', [StockAdjustmentController::class, 'index'])->name('index');
            Route::post('/', [StockAdjustmentController::class, 'store'])->name('store');
        });

        // Daily Consumption
        Route::get('/consumption', [ConsumptionController::class, 'index'])->name('consumption.index');
    });

    // ── Owner-only routes ────────────────────────────────────────────────
    Route::middleware('role:owner')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    });
});
