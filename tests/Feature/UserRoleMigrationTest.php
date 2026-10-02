<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_supervisor_accounts_are_moved_to_manager_and_role_is_removed(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['owner', 'manager', 'supervisor', 'cashier'])->default('cashier')->change();
        });

        $user = User::factory()->create(['role' => 'supervisor']);

        $migration = require database_path('migrations/2026_10_06_000001_remove_supervisor_role.php');
        $migration->up();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'manager',
        ]);
    }
}
