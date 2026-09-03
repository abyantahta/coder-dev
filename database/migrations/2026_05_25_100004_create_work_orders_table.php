<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number', 20)->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable(); // mechanical, electrical, civil, general
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('requester_id')->constrained('users');
            $table->enum('destination', ['maintenance', 'ga', 'qa'])->default('maintenance');
            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
                'forwarded_ga',
                'forwarded_qa',
                'forwarded_maintenance',
                'pending_parts',
                'parts_ordered',
                'parts_received',
                'assigned_group',
                'assigned_member',
                'completed',
                'rework',
                'finished',
                'cancelled',
            ])->default('pending');

            // Maintenance assignment
            $table->foreignId('unit_id')->nullable()->constrained('maintenance_units')->nullOnDelete();
            $table->foreignId('assigned_group_id')->nullable()->constrained('maintenance_groups')->nullOnDelete();
            $table->foreignId('assigned_member_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamps for SLA tracking
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('deadline')->nullable();       // accepted_at + 7 days
            $table->timestamp('completed_at')->nullable();   // member marks done

            // Rework
            $table->integer('rework_count')->default(0);
            $table->timestamp('rework_requested_at')->nullable();
            $table->timestamp('rework_deadline')->nullable(); // rework_requested_at + 2 days

            // Completion
            $table->timestamp('finished_at')->nullable();    // requester approves
            $table->integer('score')->nullable();            // 0-100, calculated on finish

            // Notes
            $table->text('rejection_reason')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
