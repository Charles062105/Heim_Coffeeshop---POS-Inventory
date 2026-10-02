<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['low_stock', 'out_of_stock', 'refund', 'adjustment', 'general']);
                $table->string('title');
                $table->text('message');
                $table->enum('target_role', ['owner', 'manager', 'all'])->default('manager');
                $table->foreignId('ingredient_id')->nullable()->constrained()->nullOnDelete();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('actor_name');
                $table->string('actor_role');
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');                 // e.g. "completed_order", "refund_authorized"
                $table->string('module');                 // e.g. "POS", "Inventory", "Refunds"
                $table->nullableMorphs('reference');      // polymorphic: order, refund, etc.
                $table->json('details')->nullable();      // additional context
                $table->text('reason')->nullable();       // why the action occurred
                $table->string('ip_address', 45)->nullable(); // client IP for security context
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
    }
};
