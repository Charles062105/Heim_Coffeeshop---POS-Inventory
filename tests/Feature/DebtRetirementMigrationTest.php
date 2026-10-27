<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class DebtRetirementMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_retirement_preflight_refuses_to_drop_historical_debt_records(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
        });
        DB::table('debts')->insert(['id' => 1]);

        $migration = require database_path('migrations/2026_10_05_000001_guard_retired_debt_data.php');

        try {
            $migration->up();
            $this->fail('The preflight should reject populated debt tables.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('debts', $exception->getMessage());
        }

        $this->assertDatabaseHas('debts', ['id' => 1]);
    }

    public function test_retirement_migration_rollback_restores_debt_schema(): void
    {
        $migration = require database_path('migrations/2026_10_06_000002_drop_debts_and_pay_later_tables.php');
        $migration->down();

        $this->assertTrue(Schema::hasTable('debts'));
        $this->assertTrue(Schema::hasTable('debt_payments'));
        $this->assertTrue(Schema::hasColumn('debt_payments', 'shift_id'));
        $this->assertTrue(Schema::hasColumn('refunds', 'debt_payment_id'));
        $this->assertTrue(Schema::hasColumn('cashier_shifts', 'debt_cash_collections'));
        $this->assertTrue(Schema::hasColumn('cashier_shifts', 'debt_online_collections'));
        $this->assertTrue(Schema::hasColumn('cashier_shifts', 'pay_later_charged'));
    }
}
