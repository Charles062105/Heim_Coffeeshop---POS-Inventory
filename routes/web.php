<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthorizationController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConsumptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DecisionDashboardController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductAddonController;
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
Route::middleware(['auth', 'active-user'])->group(function () {

    // ── Profile ──────────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Dashboard ────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── POS ──────────────────────────────────────────────────────────────
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/split-orders', [PosController::class, 'openSplitOrders'])->name('split-orders');
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
        // Cash In / Cash Out movements (during active shift)
        Route::prefix('cash-movements')->name('cash-movements.')->group(function () {
            Route::get('/', [ShiftController::class, 'activeCashMovements'])->name('index');   // AJAX
            Route::post('/', [ShiftController::class, 'storeCashMovement'])->name('store');
            Route::post('/{movement}/reverse', [ShiftController::class, 'reverseCashMovement'])->name('reverse');
        });
    });

    // ── Orders ───────────────────────────────────────────────────────────
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/{order}/payments', [OrderController::class, 'recordPayment'])->name('payments.store');
        Route::post('/{order}/payment-portions', [OrderController::class, 'storePaymentPortions'])->name('payments.portions.store');
        Route::get('/{order}/payments/history', [OrderController::class, 'paymentHistory'])->name('payments.history');
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
    // Note: merged into the main owner,manager group below

    // Refunds & Cancellations Actions (Authorized by manager credentials in controller)
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::post('/order/{order}/refund', [RefundController::class, 'refund'])->name('refund');
        Route::post('/order/{order}/cancel', [RefundController::class, 'cancel'])->name('cancel');
    });

    // Void Actions (Authorized by manager or owner credentials in controller)
    Route::post('/orders/{order}/void', [VoidController::class, 'voidOrder'])->name('orders.void');
    Route::post('/orders/{order}/items/{item}/void', [VoidController::class, 'voidItem'])->name('orders.items.void');

    // ── Manager / Owner routes ────────────────────────────────────────────
    Route::middleware('role:owner,manager')->group(function () {

        // Refunds & Voids History
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('/voids', [VoidController::class, 'index'])->name('voids.index');

        // Categories
        Route::resource('categories', CategoryController::class)->except(['show']);

        // Products
        Route::resource('products', ProductController::class)->except(['show']);
        Route::patch('/products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
        Route::delete('/products/{product}/sizes/{size}', [ProductController::class, 'destroySize'])->name('products.sizes.destroy');

        // Add-ons
        Route::get('/addons', [ProductAddonController::class, 'index'])->name('addons.index');
        Route::post('/addons', [ProductAddonController::class, 'store'])->name('addons.store');
        Route::put('/addons/{addon}', [ProductAddonController::class, 'update'])->name('addons.update');
        Route::put('/addons/{addon}/ingredients', [ProductAddonController::class, 'updateIngredients'])->name('addons.ingredients.update');
        Route::patch('/addons/{addon}/toggle', [ProductAddonController::class, 'toggle'])->name('addons.toggle');

        // Recipes
        Route::prefix('recipes')->name('recipes.')->group(function () {
            Route::get('/', [RecipeController::class, 'index'])->name('index');
            Route::get('/{productSize}/edit', [RecipeController::class, 'edit'])->name('edit');
            Route::put('/{productSize}', [RecipeController::class, 'update'])->name('update');
        });

        // Ingredients
        Route::resource('ingredients', IngredientController::class)->except(['show']);
        Route::patch('/ingredients/{ingredient}/toggle', [IngredientController::class, 'toggle'])->name('ingredients.toggle');

        // Inventory
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
            Route::get('/cash-movements', [ReportController::class, 'cashMovements'])->name('cash-movements');
        });

        // Decision Dashboard & Product Performance
        Route::prefix('reports/decision-dashboard')->name('reports.decision-dashboard.')->group(function () {
            Route::get('/', [DecisionDashboardController::class, 'index'])->name('index');
            Route::get('/export', [DecisionDashboardController::class, 'export'])->name('export');
            Route::get('/insights', [DecisionDashboardController::class, 'insights'])->name('insights');
            Route::post('/actions', [DecisionDashboardController::class, 'storeAction'])->name('actions.store');
            Route::get('/actions/{action}', [DecisionDashboardController::class, 'showAction'])->name('actions.show');
            Route::patch('/actions/{action}/status', [DecisionDashboardController::class, 'updateStatus'])->name('actions.status');
            Route::get('/actions/{action}/follow-up', [DecisionDashboardController::class, 'followUp'])->name('actions.follow-up');
        });
        Route::get('/decision-dashboard', fn () => redirect()->route('reports.decision-dashboard.index'))->name('decision-dashboard');

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Tax Configuration
        Route::get('/settings/tax', [TaxConfigurationController::class, 'edit'])->name('settings.tax.edit');
        Route::put('/settings/tax', [TaxConfigurationController::class, 'update'])->name('settings.tax.update');
        Route::patch('/settings/tax/archive', [TaxConfigurationController::class, 'archive'])->name('settings.tax.archive');
        Route::patch('/settings/tax/unarchive', [TaxConfigurationController::class, 'unarchive'])->name('settings.tax.unarchive');
    });

    // ── Owner-only routes ────────────────────────────────────────────────
    Route::middleware('role:owner')->group(function () {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::patch('/branches/{branch}/toggle', [BranchController::class, 'toggle'])->name('branches.toggle');
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::patch('/reports/decision-dashboard/actions/{action}/decision', [DecisionDashboardController::class, 'recordDecision'])->name('reports.decision-dashboard.actions.decision');
    });
});
