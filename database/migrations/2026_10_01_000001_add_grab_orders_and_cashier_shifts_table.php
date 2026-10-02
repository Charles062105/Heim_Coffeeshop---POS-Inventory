<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add grab_price to product_sizes
        if (! Schema::hasColumn('product_sizes', 'grab_price')) {
            Schema::table('product_sizes', function (Blueprint $table) {
                $table->decimal('grab_price', 10, 2)->nullable()->after('price');
            });
        }

        // 2. Create cashier_shifts table
        if (! Schema::hasTable('cashier_shifts')) {
            Schema::create('cashier_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('cashier_name');
                $table->date('shift_date');
                $table->timestamp('start_time');
                $table->timestamp('end_time')->nullable();
                $table->decimal('beginning_cash', 10, 2)->default(0);
                $table->decimal('cash_sales', 10, 2)->default(0);
                $table->decimal('expected_cash', 10, 2)->default(0);
                $table->decimal('actual_cash', 10, 2)->nullable();
                $table->decimal('difference', 10, 2)->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('open'); // 'open', 'closed'
                $table->timestamps();
            });
        }

        // 3. Add grab and shift fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type')->default('dine_in')->after('order_number');
            }
            if (! Schema::hasColumn('orders', 'grab_order_code')) {
                $table->string('grab_order_code')->nullable()->after('order_type');
            }
            if (! Schema::hasColumn('orders', 'rider_code')) {
                $table->string('rider_code')->nullable()->after('grab_order_code');
            }
            if (! Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('rider_code');
            }
            if (! Schema::hasColumn('orders', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('customer_name')->constrained('cashier_shifts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shift_id')) {
                $table->dropForeign(['shift_id']);
            }
            $table->dropColumn(array_filter(['order_type', 'grab_order_code', 'rider_code', 'customer_name', 'shift_id'], fn ($col) => Schema::hasColumn('orders', $col)));
        });

        Schema::dropIfExists('cashier_shifts');

        if (Schema::hasColumn('product_sizes', 'grab_price')) {
            Schema::table('product_sizes', function (Blueprint $table) {
                $table->dropColumn('grab_price');
            });
        }
    }
};
