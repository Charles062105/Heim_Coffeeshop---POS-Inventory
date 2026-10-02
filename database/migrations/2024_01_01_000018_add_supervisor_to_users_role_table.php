<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'cashier') NOT NULL DEFAULT 'cashier'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'cashier') NOT NULL DEFAULT 'cashier'");
        }
    }
};
