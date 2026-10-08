<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Models\VoidLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager', 'status' => 'active']);
    }

    public function test_audit_logs_excel_export_and_print_mode(): void
    {
        $response = $this->actingAs($this->manager)->get(route('audit-logs.index', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Log ID', $response->streamedContent());

        $printResponse = $this->actingAs($this->manager)->get(route('audit-logs.index', ['print' => 'all']));
        $printResponse->assertOk();
        $printResponse->assertSee('Security & Audit Trail');
    }

    public function test_inventory_transactions_excel_export_and_print_mode(): void
    {
        $response = $this->actingAs($this->manager)->get(route('inventory.transactions', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Transaction ID', $response->streamedContent());

        $printResponse = $this->actingAs($this->manager)->get(route('inventory.transactions', ['print' => 'all']));
        $printResponse->assertOk();
        $printResponse->assertSee('Stock Movements');
    }

    public function test_stock_adjustments_excel_export_and_print_mode(): void
    {
        $response = $this->actingAs($this->manager)->get(route('adjustments.index', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Transaction ID', $response->streamedContent());

        $printResponse = $this->actingAs($this->manager)->get(route('adjustments.index', ['print' => 'all']));
        $printResponse->assertOk();
        $printResponse->assertSee('Stock Movements');
    }

    public function test_stock_movements_include_sales_consumption_records(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Coffee Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 90]);
        InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'sales_consumption',
            'quantity' => 10,
            'previous_stock' => 100,
            'new_stock' => 90,
            'performed_by' => $this->manager->name,
            'performed_role' => 'manager',
        ]);

        $response = $this->actingAs($this->manager)->get(route('adjustments.index'));

        $response->assertOk();
        $response->assertSee('Sales Consumption');
        $this->assertSame(1, $response->viewData('transactions')->total());
        $this->assertSame(10.0, (float) $response->viewData('summary')['deductions']->first()->total);
    }

    public function test_inventory_report_subtracts_voided_sales_returns_from_net_consumption(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Returned Coffee Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 96]);

        InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'sales_consumption',
            'quantity' => 10,
            'previous_stock' => 106,
            'new_stock' => 96,
            'reason' => 'Sale: ORD-RETURN-001',
            'performed_by' => $this->manager->name,
            'performed_role' => 'manager',
        ]);
        InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'sales_return',
            'quantity' => 4,
            'previous_stock' => 96,
            'new_stock' => 100,
            'reason' => 'Void return: Sale: ORD-RETURN-001',
            'performed_by' => $this->manager->name,
            'performed_role' => 'manager',
        ]);

        $response = $this->actingAs($this->manager)->get(route('reports.inventory'));

        $response->assertOk()->assertSee('Net Ingredient Consumption via POS Sales');
        $consumption = $response->viewData('salesConsumption')->firstWhere('ingredient_id', $ingredient->id);
        $this->assertNotNull($consumption);
        $this->assertSame(6.0, (float) $consumption->total_consumed);
        $this->assertSame(0.0, (float) $consumption->recipe_consumed);
        $this->assertSame(6.0, (float) $consumption->unattributed_consumed);
    }

    public function test_inventory_report_does_not_label_legacy_consumption_as_recipe_usage(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Legacy Coffee Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 80]);

        foreach ([
            ['type' => 'sales_consumption', 'quantity' => 2, 'reference_type' => OrderItem::class, 'reason' => 'Sale: ORD-ATTRIBUTED'],
            ['type' => 'sales_consumption', 'quantity' => 3, 'reference_type' => OrderItem::class, 'reason' => "Addon 'Extra Shot' — Sale: ORD-ATTRIBUTED"],
            ['type' => 'sales_consumption', 'quantity' => 5, 'reference_type' => Order::class, 'reason' => 'Sale: ORD-LEGACY'],
            ['type' => 'sales_return', 'quantity' => 1, 'reference_type' => Order::class, 'reason' => 'Void return: Sale: ORD-LEGACY'],
        ] as $movement) {
            InventoryTransaction::create(array_merge($movement, [
                'ingredient_id' => $ingredient->id,
                'previous_stock' => 100,
                'new_stock' => 90,
                'performed_by' => $this->manager->name,
                'performed_role' => 'manager',
            ]));
        }

        $response = $this->actingAs($this->manager)->get(route('reports.inventory'));
        $consumption = $response->viewData('salesConsumption')->firstWhere('ingredient_id', $ingredient->id);

        $response->assertOk()->assertSee('Unattributed');
        $this->assertSame(2.0, (float) $consumption->recipe_consumed);
        $this->assertSame(3.0, (float) $consumption->addon_consumed);
        $this->assertSame(4.0, (float) $consumption->unattributed_consumed);
        $this->assertSame(9.0, (float) $consumption->total_consumed);

        $export = $this->actingAs($this->manager)->get(route('reports.inventory', ['export' => 'excel']));
        $this->assertStringContainsString('Unattributed Sales Usage (Period)', $export->streamedContent());
    }

    public function test_inventory_report_totals_consumption_separately_for_each_measurement_unit(): void
    {
        $beans = Ingredient::create([
            'name' => 'Coffee Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        $milk = Ingredient::create([
            'name' => 'Whole Milk',
            'unit' => 'ml',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $beans->id, 'current_stock' => 8.125]);
        Inventory::create(['ingredient_id' => $milk->id, 'current_stock' => 3.125]);

        foreach ([[$beans, 20.125], [$milk, 150.125]] as [$ingredient, $quantity]) {
            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'sales_consumption',
                'quantity' => $quantity,
                'previous_stock' => 500,
                'new_stock' => 500 - $quantity,
                'reason' => 'Sale: ORD-MIXED-UNITS',
                'performed_by' => $this->manager->name,
                'performed_role' => 'manager',
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('reports.inventory'));

        $response->assertOk()
            ->assertSee('PERIOD TOTAL (g)')
            ->assertSee('PERIOD TOTAL (ml)')
            ->assertSee('20.125 g')
            ->assertSee('150.125 ml')
            ->assertSee('8.125')
            ->assertSee('3.125');
        $this->assertSame(20.125, $response->viewData('consumptionTotals')['g']['total_consumed']);
        $this->assertSame(150.125, $response->viewData('consumptionTotals')['ml']['total_consumed']);

        $export = $this->actingAs($this->manager)->get(route('reports.inventory', ['export' => 'excel']));
        $this->assertStringContainsString('20.125', $export->streamedContent());
        $this->assertStringContainsString('150.125', $export->streamedContent());
    }

    public function test_stock_movement_display_and_export_preserve_three_decimal_places(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Fine Ground Coffee',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 10.25]);
        InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'sales_consumption',
            'quantity' => 0.125,
            'previous_stock' => 10.375,
            'new_stock' => 10.25,
            'reason' => 'Sale: ORD-PRECISION-001',
            'performed_by' => $this->manager->name,
            'performed_role' => 'manager',
        ]);

        $page = $this->actingAs($this->manager)->get(route('adjustments.index', ['type' => 'sales_consumption']));
        $page->assertOk()
            ->assertSee('−0.125')
            ->assertSee('10.375')
            ->assertSee('10.250');

        $export = $this->actingAs($this->manager)->get(route('adjustments.index', [
            'type' => 'sales_consumption',
            'export' => 'excel',
        ]));
        $this->assertStringContainsString('-0.125', $export->streamedContent());
        $this->assertStringContainsString('10.375', $export->streamedContent());
        $this->assertStringContainsString('10.250', $export->streamedContent());
    }

    public function test_consolidated_pages_render_their_tabs(): void
    {
        $this->actingAs($this->manager)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('All Orders')
            ->assertSee('Refunds')
            ->assertSee('Voids');

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Products')
            ->assertSee('Categories')
            ->assertSee('Recipes');

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Ingredients')
            ->assertSee('Stock Movements')
            ->assertDontSee('Transaction History')
            ->assertDontSee('Stock Adjustments');

        $this->get(route('reports.sales'))
            ->assertOk()
            ->assertSee('Grab')
            ->assertSee('Inventory')
            ->assertSee('Shifts')
            ->assertDontSee('Inventory Report');

        $this->get(route('refunds.index'))
            ->assertOk()
            ->assertSee('Refunds')
            ->assertDontSee('Orders Ledger');

        $this->get(route('voids.index'))
            ->assertOk()
            ->assertSee('Voids')
            ->assertDontSee('Orders Ledger');

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Security')
            ->assertSee('Tax')
            ->assertSee('Audit Logs');
    }

    public function test_sidebar_destinations_respect_user_roles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cashier', 'status' => 'active']))
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Orders')
            ->assertSee('Settings')
            ->assertDontSee('Stock')
            ->assertDontSee('Reports')
            ->assertDontSee('Users');

        $this->actingAs(User::factory()->create(['role' => 'manager', 'status' => 'active']))
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Stock')
            ->assertSee('Stock Movements')
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertSee('href="'.route('reports.sales').'"', false);
    }

    public function test_stock_in_excel_export_and_print_mode(): void
    {
        $response = $this->actingAs($this->manager)->get(route('stock-in.index', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Transaction ID', $response->streamedContent());

        $printResponse = $this->actingAs($this->manager)->get(route('stock-in.index', ['print' => 'all']));
        $printResponse->assertOk();
        $printResponse->assertSee('Stock-In Delivery');
    }

    public function test_stock_in_records_cost_dates_and_inventory_changes(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Milk',
            'unit' => 'L',
            'minimum_stock' => 2,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 3]);

        $this->actingAs($this->manager)->post(route('stock-in.store'), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 5,
            'unit_cost' => 2.5,
            'transaction_date' => now()->toDateString(),
            'expiration_date' => now()->addMonth()->toDateString(),
            'reason' => 'Weekly delivery',
        ])->assertRedirect(route('stock-in.index'));

        $this->assertEquals(8, $ingredient->fresh()->getCurrentStock());
        $transaction = InventoryTransaction::where('ingredient_id', $ingredient->id)->firstOrFail();
        $this->assertSame('stock_in', $transaction->type);
        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(2.5, $transaction->unit_cost);
        $this->assertSame(now()->toDateString(), $transaction->transaction_date->toDateString());
    }

    public function test_cashiers_cannot_record_inventory_movements(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->post(route('stock-in.store'), [])->assertForbidden();
        $this->actingAs($cashier)->post(route('adjustments.store'), [])->assertForbidden();
        $this->actingAs($cashier)->post(route('waste.store'), [])->assertForbidden();
    }

    public function test_stock_out_rejects_quantities_above_available_inventory_without_writing_a_movement(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Coffee Beans',
            'unit' => 'g',
            'minimum_stock' => 2,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 4]);

        $this->actingAs($this->manager)->post(route('waste.store'), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 5,
            'reason' => 'Waste',
        ])->assertSessionHasErrors('quantity');

        $this->assertEquals(4, $ingredient->fresh()->getCurrentStock());
        $this->assertDatabaseMissing('inventory_transactions', ['ingredient_id' => $ingredient->id]);
    }

    public function test_archived_ingredients_can_be_reconciled_but_cannot_receive_new_deliveries(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Archived Syrup',
            'unit' => 'ml',
            'minimum_stock' => 10,
            'status' => 'inactive',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 30]);

        $this->actingAs($this->manager)
            ->get(route('waste.index'))
            ->assertOk()
            ->assertSee('Archived Syrup (ml) — Archived — 30.000 current')
            ->assertSee('Archived ingredients remain available for corrections and waste records.');

        $this->actingAs($this->manager)->post(route('waste.store'), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 5,
            'reason' => 'Spoilage',
        ])->assertRedirect(route('waste.index'));

        $this->assertSame(25.0, (float) $ingredient->fresh()->getCurrentStock());
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $ingredient->id,
            'type' => 'waste',
            'quantity' => 5,
        ]);

        $this->actingAs($this->manager)->post(route('stock-in.store'), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 10,
            'unit_cost' => 2.50,
            'transaction_date' => '2026-10-08',
        ])->assertSessionHasErrors([
            'ingredient_id' => 'Unarchive this ingredient before recording a new stock-in delivery.',
        ]);

        $this->assertSame(25.0, (float) $ingredient->fresh()->getCurrentStock());
        $this->assertDatabaseMissing('inventory_transactions', [
            'ingredient_id' => $ingredient->id,
            'type' => 'stock_in',
        ]);
    }

    public function test_stock_in_date_filter_uses_the_receipt_transaction_date(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Backdated Milk Delivery',
            'unit' => 'L',
            'minimum_stock' => 2,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 0]);
        $receivedDate = now()->subDays(2)->toDateString();

        $this->actingAs($this->manager)->post(route('stock-in.store'), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 5,
            'unit_cost' => 3,
            'transaction_date' => $receivedDate,
            'reason' => 'Backdated delivery',
        ])->assertRedirect(route('stock-in.index'));

        $this->actingAs($this->manager)->get(route('stock-in.index', [
            'from' => $receivedDate,
            'to' => $receivedDate,
        ]))->assertOk()->assertSee('Backdated Milk Delivery');
    }

    public function test_inventory_created_at_fallback_filters_by_philippine_business_date(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Legacy Dated Ingredient',
            'unit' => 'L',
            'minimum_stock' => 2,
            'status' => 'active',
        ]);

        foreach ([
            ['before-business-day', '2026-10-06 15:59:59'],
            ['at-business-day-start', '2026-10-06 16:00:00'],
            ['at-next-business-day-start', '2026-10-07 16:00:00'],
        ] as [$reason, $createdAt]) {
            $transaction = InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'waste',
                'quantity' => 1,
                'previous_stock' => 10,
                'new_stock' => 9,
                'reason' => $reason,
                'performed_by' => $this->manager->name,
                'performed_role' => 'manager',
            ]);
            $transaction->forceFill([
                'transaction_date' => null,
                'created_at' => Carbon::parse($createdAt, 'UTC'),
                'updated_at' => Carbon::parse($createdAt, 'UTC'),
            ])->save();
        }

        $response = $this->actingAs($this->manager)->get(route('adjustments.index', [
            'from' => '2026-10-07',
            'to' => '2026-10-07',
        ]));

        $response->assertOk();
        $this->assertSame(
            ['at-business-day-start'],
            $response->viewData('transactions')->getCollection()->pluck('reason')->all()
        );

        $export = $this->get(route('adjustments.index', [
            'from' => '2026-10-07',
            'to' => '2026-10-07',
            'export' => 'excel',
        ]));
        $this->assertStringContainsString('2026-10-07,"2026-10-07 00:00:00"', $export->streamedContent());
    }

    public function test_stock_in_keeps_low_stock_alert_when_stock_remains_below_reorder_threshold(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Milk',
            'unit' => 'L',
            'minimum_stock' => 3,
            'reorder_level' => 10,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 5]);

        $this->actingAs($this->manager)->post(route('stock-in.store'), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 3,
            'unit_cost' => 3,
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect(route('stock-in.index'));

        $this->assertDatabaseHas('notifications', [
            'ingredient_id' => $ingredient->id,
            'type' => 'low_stock',
            'is_resolved' => false,
        ]);
    }

    public function test_inventory_report_uses_stock_in_transaction_date_and_reorder_threshold(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Backdated Report Milk',
            'unit' => 'L',
            'minimum_stock' => 3,
            'reorder_level' => 12,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 10]);
        InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'stock_in',
            'quantity' => 5,
            'previous_stock' => 5,
            'new_stock' => 10,
            'transaction_date' => now()->subDays(3)->toDateString(),
            'performed_by' => $this->manager->name,
            'performed_role' => 'manager',
        ]);

        $date = now()->subDays(3)->toDateString();
        $response = $this->actingAs($this->manager)->get(route('reports.inventory', [
            'from' => $date,
            'to' => $date,
        ]));

        $response->assertOk();
        $response->assertViewHas('stockIns', fn ($stockIns) => $stockIns->contains('ingredient_id', $ingredient->id));
        $response->assertSee('Backdated Report Milk')
            ->assertSee('12.000 L')
            ->assertDontSee('3.00 L');
    }

    public function test_inventory_report_defaults_blank_dates_and_validates_reversed_ranges(): void
    {
        $today = now(config('app.business_timezone', 'Asia/Manila'))->toDateString();

        $this->actingAs($this->manager)->get(route('reports.inventory', [
            'from' => '',
            'to' => '',
        ]))->assertOk()
            ->assertViewHas('from', $today)
            ->assertViewHas('to', $today);

        $singleDate = now(config('app.business_timezone', 'Asia/Manila'))->subDay()->toDateString();
        $this->get(route('reports.inventory', ['to' => $singleDate]))
            ->assertOk()
            ->assertViewHas('from', $singleDate)
            ->assertViewHas('to', $singleDate);

        $this->get(route('reports.inventory', [
            'from' => '2026-10-05',
            'to' => '2026-10-01',
        ]))->assertSessionHasErrors('to');
    }

    public function test_inventory_transaction_summary_keeps_units_separate(): void
    {
        $grams = Ingredient::create(['name' => 'Beans', 'unit' => 'g', 'minimum_stock' => 1, 'status' => 'active']);
        $milliliters = Ingredient::create(['name' => 'Milk', 'unit' => 'ml', 'minimum_stock' => 1, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $grams->id, 'current_stock' => 10]);
        Inventory::create(['ingredient_id' => $milliliters->id, 'current_stock' => 20]);
        foreach ([[$grams, 10.125], [$milliliters, 20.125]] as [$ingredient, $quantity]) {
            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'stock_in',
                'quantity' => $quantity,
                'previous_stock' => 0,
                'new_stock' => $quantity,
                'performed_by' => $this->manager->name,
                'performed_role' => 'manager',
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('inventory.transactions'));
        $response->assertOk()
            ->assertSee('+10.125')
            ->assertSee('+20.125');
        $additions = $response->viewData('summary')['additions'];
        $this->assertCount(2, $additions);
        $this->assertSame(['g', 'ml'], $additions->pluck('unit')->all());
        $this->assertEqualsCanonicalizing([10.125, 20.125], $additions->pluck('total')->map(fn ($total) => (float) $total)->all());
    }

    public function test_adjustment_deduction_is_displayed_as_stock_out(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 5]);
        InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'adjustment_deduct',
            'quantity' => 2,
            'previous_stock' => 7,
            'new_stock' => 5,
            'reason' => 'Count correction',
            'performed_by' => $this->manager->name,
            'performed_role' => 'manager',
        ]);

        $this->actingAs($this->manager)
            ->get(route('adjustments.index', ['type' => 'adjustment_deduct']))
            ->assertOk()
            ->assertSee('Stock-Out')
            ->assertDontSee('Deduct Stock');

        $this->assertSame('Stock-Out', InventoryTransaction::typeLabel('adjustment_deduct'));
    }

    public function test_waste_excel_export_and_print_mode(): void
    {
        $response = $this->actingAs($this->manager)->get(route('waste.index', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Transaction ID', $response->streamedContent());

        $printResponse = $this->actingAs($this->manager)->get(route('waste.index', ['print' => 'all']));
        $printResponse->assertOk();
        $printResponse->assertSee('Waste & Spoilage', false);
    }

    public function test_stock_overview_excel_export_and_print_mode(): void
    {
        $ing = Ingredient::create([
            'name' => 'Arabica Coffee Beans',
            'unit' => 'g',
            'minimum_stock' => 500.125,
            'reorder_level' => 600.875,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ing->id, 'current_stock' => 1200.375]);

        $response = $this->actingAs($this->manager)->get(route('inventory.index', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Ingredient ID', $content);
        $this->assertStringContainsString('Arabica Coffee Beans', $content);
        $this->assertStringContainsString('1200.375', $content);
        $this->assertStringContainsString('600.875', $content);
        $this->assertStringContainsString('500.125', $content);

        $printResponse = $this->actingAs($this->manager)->get(route('inventory.index', ['print' => 'all']));
        $printResponse->assertOk();
        $printResponse->assertSee('Stock Overview');
    }

    public function test_inventory_report_excel_export_and_print(): void
    {
        $ing = Ingredient::create([
            'name' => 'Robusta Coffee Beans',
            'unit' => 'g',
            'minimum_stock' => 300,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ing->id, 'current_stock' => 950]);

        $response = $this->actingAs($this->manager)->get(route('reports.inventory', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Ingredient ID', $content);
        $this->assertStringContainsString('Robusta Coffee Beans', $content);
        $this->assertStringContainsString('Stock Status', $content);

        $pageResponse = $this->actingAs($this->manager)->get(route('reports.inventory'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Inventory Report &amp; Audit', false);
        $pageResponse->assertSee('Export Excel');
        $pageResponse->assertSee('Print Report');
    }

    public function test_consumption_route_opens_net_inventory_report_for_selected_day(): void
    {
        $date = '2026-09-28';
        $response = $this->actingAs($this->manager)->get(route('consumption.index', ['date' => $date]));
        $response->assertRedirect(route('reports.inventory', [
            'from' => $date,
            'to' => $date,
        ]));
    }

    public function test_daily_consumption_report_nets_sales_returns_against_usage(): void
    {
        $date = '2026-09-28';
        $ingredient = Ingredient::create([
            'name' => 'Daily Net Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 96]);

        foreach ([
            ['sales_consumption', 10, 106, 96],
            ['sales_return', 4, 96, 100],
        ] as [$type, $quantity, $previousStock, $newStock]) {
            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'transaction_date' => $date,
                'performed_by' => $this->manager->name,
                'performed_role' => 'manager',
            ]);
        }

        $response = $this->actingAs($this->manager)
            ->followingRedirects()
            ->get(route('consumption.index', ['date' => $date]));

        $response->assertOk()->assertSee('Net Ingredient Consumption via POS Sales');
        $consumption = $response->viewData('salesConsumption')->firstWhere('ingredient_id', $ingredient->id);
        $this->assertSame(6.0, (float) $consumption->total_consumed);
    }

    public function test_sales_report_excel_export_and_print(): void
    {
        $response = $this->actingAs($this->manager)->get(route('reports.sales', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Order ID', $content);
        $this->assertStringContainsString('Order Number', $content);
        $this->assertStringContainsString('Total (PHP)', $content);

        $pageResponse = $this->actingAs($this->manager)->get(route('reports.sales'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Sales Reports &amp; Analytics', false);
        $pageResponse->assertSee('Export Excel');
        $pageResponse->assertSee('Print Report');
    }

    public function test_sales_export_lists_all_paid_methods_for_split_payments(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SPLIT-EXPORT',
            'cashier_name' => $this->manager->name,
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);
        $order->payments()->createMany([
            [
                'method' => 'cash',
                'amount_received' => 30,
                'amount_paid' => 30,
                'change_amount' => 0,
                'status' => 'paid',
                'reference_number' => null,
            ],
            [
                'method' => 'gcash',
                'amount_received' => 70,
                'amount_paid' => 70,
                'change_amount' => 0,
                'status' => 'paid',
                'reference_number' => 'GCASH-SPLIT-001',
            ],
        ]);

        $response = $this->actingAs($this->manager)->get(route('reports.sales', ['export' => 'excel']));
        $content = $response->streamedContent();

        $this->assertStringContainsString('Payment Methods', $content);
        $this->assertStringContainsString('Cash (30.00), Gcash (70.00)', $content);
        $this->assertStringContainsString('GCASH-SPLIT-001', $content);
    }

    public function test_sales_report_resolves_weekly_scope_and_validates_custom_date_ranges(): void
    {
        $weeklyResponse = $this->actingAs($this->manager)->get(route('reports.sales', [
            'type' => 'weekly',
            'date' => '2026-10-02',
        ]));

        $weeklyResponse->assertOk();
        $this->assertSame('2026-09-28', $weeklyResponse->viewData('startDate'));
        $this->assertSame('2026-10-04', $weeklyResponse->viewData('endDate'));

        $this->get(route('reports.sales', [
            'type' => 'weekly',
            'date' => '2026-10-02',
            'from' => '',
            'to' => '',
        ]))->assertOk();

        $this->get(route('reports.sales', [
            'type' => 'weekly',
            'date' => 'not-a-date',
        ]))->assertSessionHasErrors('date');

        $this->get(route('reports.sales', [
            'type' => 'custom',
            'from' => '2026-10-05',
            'to' => '2026-10-01',
        ]))->assertSessionHasErrors('to');
    }

    public function test_order_ledger_and_sales_report_filter_by_business_day(): void
    {
        foreach ([
            ['outside-business-day', '2026-10-06 15:59:59', 100],
            ['inside-business-day-start', '2026-10-06 16:00:00', 90],
            ['inside-business-day-end', '2026-10-07 15:59:59', 30],
            ['outside-next-business-day', '2026-10-07 16:00:00', 200],
        ] as [$number, $createdAt, $total]) {
            $order = Order::create([
                'order_number' => 'ORD-TZ-'.$number,
                'cashier_name' => $this->manager->name,
                'subtotal' => $total,
                'total' => $total,
                'status' => 'completed',
            ]);
            $order->forceFill([
                'created_at' => Carbon::parse($createdAt, 'UTC'),
                'updated_at' => Carbon::parse($createdAt, 'UTC'),
            ])->save();
        }

        $ordersResponse = $this->actingAs($this->manager)->get(route('orders.index', [
            'date' => '2026-10-07',
        ]));
        $ordersResponse->assertOk();
        $ordersResponse->assertSee('Oct 07, 2026');
        $ordersResponse->assertSee('11:59 PM');
        $this->assertSame(
            ['ORD-TZ-inside-business-day-end', 'ORD-TZ-inside-business-day-start'],
            $ordersResponse->viewData('orders')->getCollection()->pluck('order_number')->sort()->values()->all()
        );

        $reportResponse = $this->get(route('reports.sales', [
            'type' => 'daily',
            'date' => '2026-10-07',
        ]));
        $reportResponse->assertOk();
        $reportResponse->assertViewHas('totalSales', 120.0);
        $reportResponse->assertViewHas('totalOrders', 2);
        $this->assertSame('2026-10-07', $reportResponse->viewData('dailyBreakdown')->first()->date);
        $this->assertSame(120.0, $reportResponse->viewData('dailyBreakdown')->first()->total);

        $exportResponse = $this->get(route('reports.sales', [
            'type' => 'daily',
            'date' => '2026-10-07',
            'export' => 'excel',
        ]));
        $this->assertStringContainsString('2026-10-07 23:59:59', $exportResponse->streamedContent());
    }

    public function test_void_history_filters_by_philippine_business_day(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TZ-VOID',
            'cashier_name' => $this->manager->name,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'voided',
        ]);

        foreach ([
            ['outside-business-day', '2026-10-06 15:59:59'],
            ['inside-business-day-start', '2026-10-06 16:00:00'],
            ['inside-business-day-end', '2026-10-07 15:59:59'],
            ['outside-next-business-day', '2026-10-07 16:00:00'],
        ] as [$reason, $voidedAt]) {
            $void = VoidLog::create([
                'order_id' => $order->id,
                'amount' => 100,
                'void_type' => 'order',
                'reason' => $reason,
                'cashier_name' => $this->manager->name,
                'authorized_by' => $this->manager->name,
                'authorized_role' => 'manager',
                'stock_restored' => false,
                'voided_at' => Carbon::parse($voidedAt, 'UTC'),
            ]);
            $void->forceFill([
                'created_at' => Carbon::parse($voidedAt, 'UTC'),
                'updated_at' => Carbon::parse($voidedAt, 'UTC'),
            ])->save();
        }

        $response = $this->actingAs($this->manager)->get(route('voids.index', [
            'from' => '2026-10-07',
            'to' => '2026-10-07',
        ]));

        $response->assertOk();
        $this->assertSame(
            ['inside-business-day-end', 'inside-business-day-start'],
            $response->viewData('voidLogs')->getCollection()->pluck('reason')->all()
        );
    }

    public function test_refund_history_filters_by_refund_timestamp_in_philippine_time(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TZ-REFUND',
            'cashier_name' => $this->manager->name,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'refunded',
        ]);

        foreach ([
            ['outside-business-day', '2026-10-06 15:59:59', '2026-10-06 15:59:59'],
            ['inside-business-day-start', '2026-10-05 12:00:00', '2026-10-06 16:00:00'],
            ['inside-business-day-end', '2026-10-08 12:00:00', '2026-10-07 15:59:59'],
            ['outside-next-business-day', '2026-10-07 15:00:00', '2026-10-07 16:00:00'],
        ] as [$reason, $createdAt, $refundedAt]) {
            $refund = Refund::create([
                'order_id' => $order->id,
                'amount' => 100,
                'method' => 'cash',
                'status' => 'completed',
                'reason' => $reason,
                'authorized_by' => $this->manager->name,
                'authorized_role' => 'manager',
                'stock_restored' => false,
                'refunded_at' => Carbon::parse($refundedAt, 'UTC'),
            ]);
            $refund->forceFill([
                'created_at' => Carbon::parse($createdAt, 'UTC'),
                'updated_at' => Carbon::parse($createdAt, 'UTC'),
            ])->save();
        }

        $processingRefund = Refund::create([
            'order_id' => $order->id,
            'amount' => 100,
            'method' => 'online',
            'status' => 'processing',
            'reason' => 'processing-refund-request',
            'authorized_by' => $this->manager->name,
            'authorized_role' => 'manager',
            'stock_restored' => false,
            'refunded_at' => null,
        ]);
        $processingRefund->forceFill([
            'created_at' => Carbon::parse('2026-10-07 12:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-10-07 12:00:00', 'UTC'),
        ])->save();

        $response = $this->actingAs($this->manager)->get(route('refunds.index', [
            'from' => '2026-10-07',
            'to' => '2026-10-07',
        ]));

        $response->assertOk();
        $this->assertSame(
            ['inside-business-day-end', 'processing-refund-request', 'inside-business-day-start'],
            $response->viewData('refunds')->getCollection()->pluck('reason')->all()
        );
    }

    public function test_sales_report_payment_breakdown_excludes_unpaid_payment_attempts(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-REPORT-PAYMENTS-001',
            'cashier_name' => $this->manager->name,
            'subtotal' => 120,
            'total' => 120,
            'status' => 'partially_paid',
        ]);

        foreach ([
            ['status' => 'paid', 'amount_paid' => 30],
            ['status' => 'pending', 'amount_paid' => 40],
            ['status' => 'failed', 'amount_paid' => 50],
        ] as $paymentData) {
            Payment::create([
                'order_id' => $order->id,
                'method' => 'online',
                'amount_paid' => $paymentData['amount_paid'],
                'amount_received' => $paymentData['status'] === 'paid' ? $paymentData['amount_paid'] : 0,
                'change_amount' => 0,
                'status' => $paymentData['status'],
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('reports.sales', ['type' => 'all']));

        $response->assertOk()->assertSee('Sales Orders')->assertDontSee('Paid Orders');
        $paymentBreakdown = $response->viewData('paymentBreakdown');
        $this->assertCount(1, $paymentBreakdown);
        $this->assertSame('online', $paymentBreakdown->first()->method);
        $this->assertSame(30.0, (float) $paymentBreakdown->first()->total);
        $this->assertSame(1, $paymentBreakdown->first()->count);
    }

    public function test_sales_report_keeps_refunded_orders_and_original_tender_visible(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-REPORT-REFUNDED-SALE',
            'cashier_name' => $this->manager->name,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'refunded',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'amount_received' => 100,
            'amount_paid' => 100,
            'change_amount' => 0,
            'status' => 'refunded',
        ]);
        Refund::create([
            'order_id' => $order->id,
            'amount' => 100,
            'method' => 'cash',
            'status' => 'completed',
            'reason' => 'Fully refunded report test',
            'authorized_by' => $this->manager->name,
            'authorized_role' => 'manager',
            'stock_restored' => false,
            'refunded_at' => now(),
        ]);

        $response = $this->actingAs($this->manager)->get(route('reports.sales', ['type' => 'all']));

        $response->assertOk();
        $response->assertViewHas('totalSales', 100.0);
        $response->assertViewHas('totalOrders', 1);
        $response->assertViewHas('refundsAmount', 100.0);
        $paymentBreakdown = $response->viewData('paymentBreakdown');
        $this->assertSame('cash', $paymentBreakdown->first()->method);
        $this->assertSame(100.0, (float) $paymentBreakdown->first()->total);

        $export = $this->get(route('reports.sales', ['type' => 'all', 'export' => 'excel']));
        $this->assertStringContainsString('Cash (100.00)', $export->streamedContent());
    }

    public function test_sales_report_counts_completed_refunds_by_refund_date_not_order_date(): void
    {
        $orders = collect([
            ['number' => 'ORD-REFUND-EVENT-IN', 'created' => '2026-10-08 12:00:00'],
            ['number' => 'ORD-REFUND-EVENT-OUT', 'created' => '2026-10-07 12:00:00'],
            ['number' => 'ORD-REFUND-EVENT-FALLBACK', 'created' => '2026-10-07 12:00:00'],
            ['number' => 'ORD-REFUND-EVENT-PROCESSING', 'created' => '2026-10-07 12:00:00'],
        ])->map(function (array $data) {
            $order = Order::create([
                'order_number' => $data['number'],
                'cashier_name' => $this->manager->name,
                'subtotal' => 100,
                'total' => 100,
                'status' => 'refunded',
            ]);
            $order->forceFill([
                'created_at' => Carbon::parse($data['created'], 'UTC'),
                'updated_at' => Carbon::parse($data['created'], 'UTC'),
            ])->save();

            return $order;
        });

        foreach ([
            [$orders[0], 25, 'completed', '2026-10-07 15:59:59', '2026-10-07 15:00:00'],
            [$orders[1], 100, 'completed', '2026-10-08 16:00:00', '2026-10-07 12:00:00'],
            [$orders[2], 5, 'completed', null, '2026-10-07 15:00:00'],
            [$orders[3], 50, 'processing', null, '2026-10-07 15:00:00'],
        ] as [$order, $amount, $status, $refundedAt, $createdAt]) {
            $refund = Refund::create([
                'order_id' => $order->id,
                'amount' => $amount,
                'method' => 'online',
                'status' => $status,
                'reason' => 'Report date test',
                'authorized_by' => $this->manager->name,
                'authorized_role' => 'manager',
                'stock_restored' => false,
                'refunded_at' => $refundedAt ? Carbon::parse($refundedAt, 'UTC') : null,
            ]);
            $refund->forceFill([
                'created_at' => Carbon::parse($createdAt, 'UTC'),
                'updated_at' => Carbon::parse($createdAt, 'UTC'),
            ])->save();
        }

        $response = $this->actingAs($this->manager)->get(route('reports.sales', [
            'type' => 'custom',
            'from' => '2026-10-07',
            'to' => '2026-10-07',
        ]));

        $response->assertOk()->assertSee('Completed Refunds')->assertSee('₱30.00 total refunded');
        $this->assertSame(2, $response->viewData('refundsCount'));
        $this->assertSame(30.0, $response->viewData('refundsAmount'));
    }

    public function test_grab_orders_report_excel_export_and_page(): void
    {
        $response = $this->actingAs($this->manager)->get(route('reports.grab', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Grab Order Code', $content);
        $this->assertStringContainsString('Rider Code', $content);

        $pageResponse = $this->actingAs($this->manager)->get(route('reports.grab'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Grab Orders &amp; Analytics', false);
        $pageResponse->assertSee('Export Excel');
        $pageResponse->assertSee('Print Report');
    }

    public function test_grab_report_export_includes_refunded_payment_methods_and_amounts(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-GRAB-REFUNDED-001',
            'order_type' => 'grab',
            'grab_order_code' => 'GF-REFUNDED-001',
            'cashier_name' => $this->manager->name,
            'subtotal' => 125,
            'total' => 125,
            'status' => 'refunded',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'method' => 'grabfood',
            'amount_received' => 125,
            'amount_paid' => 125,
            'change_amount' => 0,
            'status' => 'refunded',
        ]);

        $response = $this->actingAs($this->manager)->get(route('reports.grab', ['export' => 'excel']));

        $response->assertOk();
        $this->assertStringContainsString('GrabFood (125.00)', $response->streamedContent());
        $this->assertStringContainsString('refunded', strtolower($response->streamedContent()));
    }

    public function test_grab_and_shift_report_filters_reject_unknown_statuses_and_invalid_dates(): void
    {
        $this->actingAs($this->manager)
            ->get(route('reports.grab', ['status' => 'not-a-status']))
            ->assertSessionHasErrors('status');

        $this->actingAs($this->manager)
            ->get(route('reports.shifts', ['from' => 'not-a-date', 'status' => 'not-a-status']))
            ->assertSessionHasErrors(['from', 'status']);
    }

    public function test_cashier_shifts_report_excel_export_and_page(): void
    {
        $response = $this->actingAs($this->manager)->get(route('reports.shifts', ['export' => 'excel']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Shift ID', $content);
        $this->assertStringContainsString('Beginning Cash', $content);

        $pageResponse = $this->actingAs($this->manager)->get(route('reports.shifts'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Cashier Shifts &amp; Cash Reconciliation', false);
        $pageResponse->assertSee('Export Excel');
        $pageResponse->assertSee('Print Report');
    }

    public function test_manager_can_close_by_denomination_review_and_record_audit_only_adjustment(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'status' => 'active']);
        $this->actingAs($cashier)->post(route('shifts.start'), ['beginning_cash' => 100])
            ->assertSessionHasNoErrors();
        $shift = CashierShift::activeForUser($cashier->id);

        $this->actingAs($this->manager)->post(route('reports.shifts.close', $shift), [
            'denomination_count' => ['100' => 1],
        ])->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertSame('closed', $shift->status);
        $this->assertSame('100.00', $shift->actual_cash);
        $this->assertSame('0.00', $shift->difference);

        $this->actingAs($this->manager)->post(route('reports.shifts.review', $shift), [
            'review_note' => 'Drawer count verified.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->manager)->from(route('reports.shifts.show', $shift))
            ->post(route('reports.shifts.adjustments.store', $shift), [
                'amount' => '12.501',
                'reason' => 'Correcting documented cash discrepancy',
            ])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('shift_cash_movements', 0);

        $this->actingAs($this->manager)->post(route('reports.shifts.adjustments.store', $shift), [
            'amount' => '12.50',
            'reason' => 'Correcting documented cash discrepancy',
        ])->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertSame('reviewed', $shift->status);
        $this->assertSame('0.00', $shift->difference);
        $this->assertDatabaseHas('shift_cash_movements', [
            'shift_id' => $shift->id,
            'recorded_by' => $this->manager->id,
            'type' => 'adjustment',
            'amount' => 12.50,
        ]);

        $this->actingAs($this->manager)->get(route('reports.shifts.show', $shift))
            ->assertOk()
            ->assertSee('Correction adjustment')
            ->assertSee('Correcting documented cash discrepancy');

        $activeCashier = User::factory()->create(['role' => 'cashier', 'status' => 'active']);
        $this->actingAs($activeCashier)->post(route('shifts.start'), ['beginning_cash' => 100])
            ->assertSessionHasNoErrors();
        $activeShift = CashierShift::activeForUser($activeCashier->id);

        $this->actingAs($this->manager)->get(route('reports.shifts.show', $activeShift))
            ->assertOk()
            ->assertSee('Count by denomination instead')
            ->assertSee('denomination_count[1000]');
    }

    public function test_active_shift_report_calculates_expected_cash_from_live_payments(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'status' => 'active']);
        $this->actingAs($cashier)->post(route('shifts.start'), ['beginning_cash' => 100])
            ->assertSessionHasNoErrors();
        $shift = CashierShift::activeForUser($cashier->id);
        $order = Order::create([
            'order_number' => 'ORD-LIVE-SHIFT-001',
            'cashier_name' => $cashier->name,
            'shift_id' => $shift->id,
            'subtotal' => 20,
            'total' => 20,
            'status' => 'completed',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'shift_id' => $shift->id,
            'method' => 'cash',
            'amount_received' => 20,
            'amount_paid' => 20,
            'change_amount' => 0,
            'status' => 'paid',
        ]);

        $this->actingAs($cashier)->getJson(route('shifts.current'))
            ->assertOk()
            ->assertJsonPath('shift.non_cash_summary.dine_in_sales', 20)
            ->assertJsonPath('shift.non_cash_summary.take_out_sales', 0);

        $this->actingAs($this->manager)->get(route('reports.shifts'))
            ->assertOk()
            ->assertSee('Cash Voids')
            ->assertSee('₱20.00')
            ->assertSee('₱120.00');
    }

    public function test_manager_must_document_held_ticket_override_when_closing_another_cashiers_shift(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'status' => 'active']);
        $this->actingAs($cashier)->post(route('shifts.start'), ['beginning_cash' => 100])
            ->assertSessionHasNoErrors();
        $shift = CashierShift::activeForUser($cashier->id);
        Order::create([
            'order_number' => 'ORD-MANAGER-OVERRIDE-001',
            'cashier_name' => $cashier->name,
            'shift_id' => $shift->id,
            'subtotal' => 50,
            'total' => 50,
            'status' => 'held',
        ]);

        $this->actingAs($this->manager)->post(route('reports.shifts.close', $shift), [
            'actual_cash' => 100,
        ])->assertSessionHasErrors('override_reason');
        $this->assertSame('open', $shift->fresh()->status);

        $this->actingAs($this->manager)->post(route('reports.shifts.close', $shift), [
            'actual_cash' => 100,
            'override_reason' => 'Approved after confirming held ticket details.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cashier_shifts', [
            'id' => $shift->id,
            'status' => 'closed',
            'closed_by' => $this->manager->id,
            'difference' => 0,
        ]);
    }
}
