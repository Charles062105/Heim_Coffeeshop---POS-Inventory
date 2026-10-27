<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $mainBranch;

    private Branch $uptownBranch;

    private User $owner;

    private User $mainManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mainBranch = Branch::where('code', 'MAIN')->firstOrFail();
        $this->uptownBranch = Branch::create([
            'name' => 'Uptown',
            'code' => 'UPTOWN',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'branch_id' => $this->mainBranch->id,
        ]);
        $this->mainManager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->mainBranch->id,
        ]);
    }

    public function test_branch_accounts_only_see_their_branch_stock_even_if_they_request_another_branch(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Branch Test Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->mainBranch->id,
            'current_stock' => 5,
        ]);
        Inventory::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->uptownBranch->id,
            'current_stock' => 50,
        ]);

        $this->actingAs($this->mainManager)
            ->get(route('inventory.index', ['branch_id' => $this->uptownBranch->id]))
            ->assertOk()
            ->assertSee('Your assigned branch: Main Branch')
            ->assertSee('5.000')
            ->assertDontSee('50.000');
    }

    public function test_sales_report_combines_branch_sales_for_owner_and_can_filter_one_branch(): void
    {
        Order::create([
            'branch_id' => $this->mainBranch->id,
            'order_number' => 'MAIN-REPORT-1',
            'cashier_name' => 'Main Cashier',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'completed',
        ]);
        Order::create([
            'branch_id' => $this->uptownBranch->id,
            'order_number' => 'UPTOWN-REPORT-1',
            'cashier_name' => 'Uptown Cashier',
            'subtotal' => 250,
            'total' => 250,
            'status' => 'completed',
        ]);

        $this->actingAs($this->owner)
            ->get(route('reports.sales', ['type' => 'all']))
            ->assertOk()
            ->assertSee('350.00')
            ->assertSee('Main Branch')
            ->assertSee('Uptown')
            ->assertSee('Combined Total')
            ->assertViewHas('totalSales', fn ($totalSales) => (float) $totalSales === 350.0)
            ->assertViewHas('branchSales', fn ($branchSales) => (float) $branchSales->sum('total_sales') === 350.0);

        $this->actingAs($this->owner)
            ->get(route('reports.sales', ['type' => 'all', 'branch_id' => $this->uptownBranch->id]))
            ->assertOk()
            ->assertSee('250.00')
            ->assertSee('Combined Total')
            ->assertViewHas('totalSales', fn ($totalSales) => (float) $totalSales === 250.0)
            ->assertViewHas('branchSales', fn ($branchSales) => (float) $branchSales->sum('total_sales') === 350.0);
    }

    public function test_branch_staff_cannot_post_stock_to_another_branch(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Branch Stock Entry Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->mainBranch->id,
            'current_stock' => 5,
        ]);
        Inventory::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->uptownBranch->id,
            'current_stock' => 50,
        ]);

        $this->actingAs($this->mainManager)
            ->post(route('adjustments.store'), [
                'ingredient_id' => $ingredient->id,
                'type' => 'adjustment_add',
                'quantity' => 2,
                'reason' => 'Branch count correction',
                'branch_id' => $this->uptownBranch->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventory', [
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->mainBranch->id,
            'current_stock' => 7,
        ]);
        $this->assertDatabaseHas('inventory', [
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->uptownBranch->id,
            'current_stock' => 50,
        ]);
        $this->assertSame(
            $this->mainBranch->id,
            InventoryTransaction::where('ingredient_id', $ingredient->id)->firstOrFail()->branch_id
        );
    }

    public function test_branch_manager_cannot_bypass_branch_sales_filter(): void
    {
        Order::create([
            'branch_id' => $this->mainBranch->id,
            'order_number' => 'MAIN-MANAGER-1',
            'cashier_name' => 'Main Cashier',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'completed',
        ]);
        Order::create([
            'branch_id' => $this->uptownBranch->id,
            'order_number' => 'UPTOWN-MANAGER-1',
            'cashier_name' => 'Uptown Cashier',
            'subtotal' => 250,
            'total' => 250,
            'status' => 'completed',
        ]);

        $this->actingAs($this->mainManager)
            ->get(route('reports.sales', ['type' => 'all', 'branch_id' => $this->uptownBranch->id]))
            ->assertOk()
            ->assertSee('100.00')
            ->assertDontSee('Uptown')
            ->assertViewHas('totalSales', fn ($totalSales) => (float) $totalSales === 100.0);
    }

    public function test_owner_can_assign_a_new_staff_account_to_a_branch(): void
    {
        $this->actingAs($this->owner)
            ->post(route('users.store'), [
                'name' => 'Uptown Manager',
                'email' => 'uptown.manager@example.com',
                'password' => 'CoffeeShop123!',
                'password_confirmation' => 'CoffeeShop123!',
                'role' => 'manager',
                'branch_id' => $this->uptownBranch->id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'uptown.manager@example.com',
            'branch_id' => $this->uptownBranch->id,
        ]);
    }
}
