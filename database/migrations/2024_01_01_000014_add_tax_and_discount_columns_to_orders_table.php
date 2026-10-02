<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('discount'); // 'none', 'senior', 'pwd', 'custom'
            $table->string('discount_label')->nullable()->after('discount_type');
            $table->string('discount_id_number')->nullable()->after('discount_label');
            $table->string('tax_name')->default('VAT')->after('total');
            $table->decimal('tax_rate', 5, 2)->default(12.00)->after('tax_name');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
            $table->decimal('vatable_sales', 10, 2)->default(0)->after('tax_amount');
            $table->decimal('vat_exempt_sales', 10, 2)->default(0)->after('vatable_sales');
            $table->decimal('zero_rated_sales', 10, 2)->default(0)->after('vat_exempt_sales');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type',
                'discount_label',
                'discount_id_number',
                'tax_name',
                'tax_rate',
                'tax_amount',
                'vatable_sales',
                'vat_exempt_sales',
                'zero_rated_sales',
            ]);
        });
    }
};
