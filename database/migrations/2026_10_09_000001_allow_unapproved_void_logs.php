<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('void_logs', function (Blueprint $table) {
            $table->string('authorized_by')->nullable()->change();
            $table->string('authorized_role')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('void_logs')
            ->whereNull('authorized_by')
            ->update(['authorized_by' => 'Not required']);
        DB::table('void_logs')
            ->whereNull('authorized_role')
            ->update(['authorized_role' => 'not_required']);

        Schema::table('void_logs', function (Blueprint $table) {
            $table->string('authorized_by')->nullable(false)->change();
            $table->string('authorized_role')->nullable(false)->change();
        });
    }
};
