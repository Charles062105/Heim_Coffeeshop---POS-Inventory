<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('order_items', 'assigned_to')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('assigned_to', 150)->nullable();
            });
        }

        if (! Schema::hasColumn('order_items', 'payable_total')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('payable_total', 10, 2)->default(0);
            });
        }

        $paymentForeignKey = collect(Schema::getForeignKeys('payments'))
            ->first(fn (array $foreignKey) => $foreignKey['columns'] === ['order_id']);

        if ($paymentForeignKey) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });
        }

        $orderIdIndex = collect(Schema::getIndexes('payments'))
            ->first(fn (array $index) => $index['columns'] === ['order_id'] && $index['unique'] && ! $index['primary']);

        if ($orderIdIndex) {
            Schema::table('payments', function (Blueprint $table) use ($orderIdIndex) {
                $table->dropUnique($orderIdIndex['name']);
            });
        }

        $orderIdIndex = collect(Schema::getIndexes('payments'))
            ->first(fn (array $index) => $index['columns'] === ['order_id']);

        if (! $orderIdIndex) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('order_id');
            });
        }

        if (! collect(Schema::getForeignKeys('payments'))->contains(fn (array $foreignKey) => $foreignKey['columns'] === ['order_id'])) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
            if (! Schema::hasColumn('payments', 'customer_name')) {
                $table->string('customer_name', 150)->nullable();
            }
            if (! Schema::hasColumn('payments', 'comment')) {
                $table->string('comment', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (['customer_name', 'comment'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                Schema::table('payments', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        $paymentForeignKey = collect(Schema::getForeignKeys('payments'))
            ->first(fn (array $foreignKey) => $foreignKey['columns'] === ['order_id']);

        if ($paymentForeignKey) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });
        }

        foreach (Schema::getIndexes('payments') as $index) {
            if ($index['columns'] === ['order_id'] && ! $index['unique'] && ! $index['primary']) {
                Schema::table('payments', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index['name']);
                });
            }
        }

        if (! Schema::hasIndex('payments', ['order_id'], 'unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('order_id');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            foreach (['assigned_to', 'payable_total'] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
