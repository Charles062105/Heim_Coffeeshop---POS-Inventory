<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'manager']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'cashier') NOT NULL DEFAULT 'cashier'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['owner', 'manager', 'cashier'])->default('cashier')->change();
            });
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'supervisor', 'cashier') NOT NULL DEFAULT 'cashier'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['owner', 'manager', 'supervisor', 'cashier'])->default('cashier')->change();
            });
        }
    }
};
