<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'request_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->uuid('request_id')->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'request_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique(['request_id']);
                $table->dropColumn('request_id');
            });
        }
    }
};
