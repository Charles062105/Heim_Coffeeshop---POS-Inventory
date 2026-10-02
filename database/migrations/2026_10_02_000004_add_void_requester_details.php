<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('void_logs', function (Blueprint $table) {
            $table->foreignId('requested_by_user_id')->nullable()->after('cashier_name')->constrained('users')->nullOnDelete();
            $table->string('requested_by')->nullable()->after('requested_by_user_id');
            $table->string('requested_role')->nullable()->after('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('void_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by_user_id');
            $table->dropColumn(['requested_by', 'requested_role']);
        });
    }
};
