<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\ShiftCashMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashMovementTest extends TestCase
{
    use RefreshDatabase;

    private function getBranch(): Branch
    {
        return Branch::where('code', 'MAIN')->first() ?? Branch::create([
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_record_cash_in_directly(): void
    {
        $branch = $this->getBranch();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($manager)
            ->postJson(route('shifts.start'), ['beginning_cash' => 1000])
            ->assertOk();

        $response = $this->actingAs($manager)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_in',
                'amount' => 500,
                'reason' => 'Change fund replenishment',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('movement.type', 'cash_in')
            ->assertJsonPath('movement.amount', 500)
            ->assertJsonPath('movement.status', 'approved');

        $this->assertDatabaseHas('shift_cash_movements', [
            'type' => 'cash_in',
            'amount' => 500,
            'status' => 'approved',
            'reason' => 'Change fund replenishment',
            'cashier_id' => $manager->id,
            'authorized_by' => $manager->id,
            'branch_id' => $branch->id,
        ]);

        $shift = CashierShift::first();
        $this->assertEquals(500, $shift->cashSummary()['cash_in']);
        $this->assertEquals(1500, $shift->expectedCash());
    }

    public function test_cashier_requires_manager_credentials_to_record_cash_in(): void
    {
        $branch = $this->getBranch();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
            'password' => bcrypt('manager-secret-123'),
        ]);
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($cashier)
            ->postJson(route('shifts.start'), ['beginning_cash' => 500])
            ->assertOk();

        // 1. Without credentials -> 422 error
        $this->actingAs($cashier)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_in',
                'amount' => 300,
                'reason' => 'Petty cash top-up',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['authorizer_email']);

        // 2. With invalid credentials -> 422 error
        $this->actingAs($cashier)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_in',
                'amount' => 300,
                'reason' => 'Petty cash top-up',
                'authorizer_email' => $manager->email,
                'authorizer_password' => 'wrong-pass',
            ])
            ->assertStatus(422);

        // 3. With valid manager credentials -> Success
        $this->actingAs($cashier)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_in',
                'amount' => 300,
                'reason' => 'Petty cash top-up',
                'authorizer_email' => $manager->email,
                'authorizer_password' => 'manager-secret-123',
            ])
            ->assertOk()
            ->assertJsonPath('movement.authorized_by', $manager->name);

        $this->assertDatabaseHas('shift_cash_movements', [
            'cashier_id' => $cashier->id,
            'authorized_by' => $manager->id,
            'amount' => 300,
            'type' => 'cash_in',
        ]);
    }

    public function test_cash_out_cannot_exceed_available_drawer_cash(): void
    {
        $branch = $this->getBranch();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($manager)
            ->postJson(route('shifts.start'), ['beginning_cash' => 400])
            ->assertOk();

        // Attempting to withdraw 600 when only 400 is in drawer
        $this->actingAs($manager)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_out',
                'amount' => 600,
                'reason' => 'Drop to safe',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cash Out amount (₱600.00) exceeds available drawer cash (₱400.00).');

        $this->assertDatabaseMissing('shift_cash_movements', [
            'type' => 'cash_out',
        ]);
    }

    public function test_cash_out_succeeds_and_reduces_drawer_cash(): void
    {
        $branch = $this->getBranch();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($manager)
            ->postJson(route('shifts.start'), ['beginning_cash' => 1000])
            ->assertOk();

        $this->actingAs($manager)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_out',
                'amount' => 350,
                'reason' => 'Drop to safe',
            ])
            ->assertOk();

        $shift = CashierShift::first();
        $this->assertEquals(350, $shift->cashSummary()['cash_out']);
        $this->assertEquals(650, $shift->availableCash());
        $this->assertEquals(650, $shift->expectedCash());
    }

    public function test_cash_movement_reversal_workflow(): void
    {
        $branch = $this->getBranch();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($manager)
            ->postJson(route('shifts.start'), ['beginning_cash' => 500])
            ->assertOk();

        $this->actingAs($manager)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_in',
                'amount' => 200,
                'reason' => 'Incorrect entry test',
            ])
            ->assertOk();

        $movement = ShiftCashMovement::first();
        $this->assertEquals('approved', $movement->status);

        // Reverse the movement
        $this->actingAs($manager)
            ->postJson(route('shifts.cash-movements.reverse', $movement), [
                'reversal_reason' => 'Accidental duplicate entry by cashier',
            ])
            ->assertOk()
            ->assertJsonPath('movement.status', 'reversed');

        $movement->refresh();
        $this->assertEquals('reversed', $movement->status);
        $this->assertEquals($manager->id, $movement->reversed_by);
        $this->assertNotNull($movement->reversed_at);
        $this->assertEquals('Accidental duplicate entry by cashier', $movement->reversal_reason);

        // Reversed movements are excluded from cashSummary and expected cash
        $shift = CashierShift::first();
        $this->assertEquals(0, $shift->cashSummary()['cash_in']);
        $this->assertEquals(500, $shift->expectedCash());
    }

    public function test_shift_preview_end_and_close_integrates_cash_movements(): void
    {
        $branch = $this->getBranch();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($manager)
            ->postJson(route('shifts.start'), ['beginning_cash' => 1000])
            ->assertOk();

        $this->actingAs($manager)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_in',
                'amount' => 500,
                'reason' => 'Float add',
            ])->assertOk();

        $this->actingAs($manager)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_out',
                'amount' => 200,
                'reason' => 'Store supply cash buy',
            ])->assertOk();

        // Preview shift close: 1000 + 500 - 200 = 1300 expected
        $preview = $this->actingAs($manager)
            ->postJson(route('shifts.preview-end'), ['actual_cash' => 1300])
            ->assertOk()
            ->json();

        $this->assertEquals(1300, $preview['expected_cash']);
        $this->assertEquals(0, $preview['difference']);
        $this->assertEquals(500, $preview['summary']['cash_in']);
        $this->assertEquals(200, $preview['summary']['cash_out']);

        // End shift
        $this->actingAs($manager)
            ->postJson(route('shifts.end'), [
                'actual_cash' => 1300,
                'comment' => 'Balanced drawer closing',
            ])
            ->assertOk();

        $shift = CashierShift::first();
        $this->assertEquals('closed', $shift->status);
        $this->assertEquals(500, (float) $shift->cash_in);
        $this->assertEquals(200, (float) $shift->cash_out);
        $this->assertEquals(1300, (float) $shift->actual_cash);
        $this->assertEquals(0, (float) $shift->difference);
    }

    public function test_cash_movements_report_page_and_export(): void
    {
        $branch = $this->getBranch();
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($manager)
            ->postJson(route('shifts.start'), ['beginning_cash' => 500])
            ->assertOk();

        $this->actingAs($manager)
            ->postJson(route('shifts.cash-movements.store'), [
                'type' => 'cash_in',
                'amount' => 150,
                'reason' => 'Coin float replenishment',
            ])->assertOk();

        // 1. View report page
        $this->actingAs($manager)
            ->get(route('reports.cash-movements'))
            ->assertOk()
            ->assertSee('Cash Movements')
            ->assertSee('Coin float replenishment');

        // 2. CSV export
        $export = $this->actingAs($manager)
            ->get(route('reports.cash-movements', ['export' => 'excel']))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $export->headers->get('content-type'));
    }
}
