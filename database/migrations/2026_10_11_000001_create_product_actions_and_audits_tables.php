<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_actions')) {
            Schema::create('product_actions', function (Blueprint $table) {
                $table->id();
                $table->string('action_code')->unique();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('source_reference')->nullable();
                $table->string('finding_category'); // High sales, Low sales, Zero sales, Declining sales, Increasing sales, Low ingredient stock, High inventory with low sales, Branch performance difference, Insufficient data
                $table->text('evidence_summary');
                $table->string('reporting_period')->nullable();
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->text('recommended_action');
                $table->string('owner_decision')->default('Needs Review'); // Needs Review, Approved, Rejected, In Progress, Completed, Deferred
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('assigned_to_name')->nullable();
                $table->date('due_date')->nullable();
                $table->string('status')->default('needs_review'); // needs_review, approved, rejected, in_progress, completed, deferred
                $table->text('completion_notes')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('follow_up_period')->nullable();
                $table->date('follow_up_start')->nullable();
                $table->date('follow_up_end')->nullable();
                $table->json('result_metrics')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('created_by_name')->nullable();
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('updated_by_name')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'status']);
                $table->index(['product_id', 'status']);
                $table->index('finding_category');
                $table->index('owner_decision');
            });
        }

        if (! Schema::hasTable('product_action_audits')) {
            Schema::create('product_action_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_action_id')->constrained('product_actions')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name');
                $table->string('user_role');
                $table->string('event'); // created, decision_recorded, assigned, status_updated, completed, follow_up_evaluated
                $table->string('previous_decision')->nullable();
                $table->string('new_decision')->nullable();
                $table->string('previous_status')->nullable();
                $table->string('new_status')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['product_action_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_action_audits');
        Schema::dropIfExists('product_actions');
    }
};
