<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->string('name');

            // actor_role_id: who acts on this step. null = determined by step_type context
            $table->foreignId('actor_role_id')->nullable()->constrained('department_roles')->nullOnDelete();

            $table->enum('step_type', [
                'standard',          // simple accept/reject/forward
                'spare_parts_check', // MTC: fill spare parts form
                'assign',            // actor selects another user to assign
                'material_check',    // assigned staffer checks material availability themselves (e.g. GA)
                'completion',        // assigned_member marks work done
                'requester_review',  // requester approves or requests rework
            ])->default('standard');

            $table->boolean('can_reject')->default(false);
            $table->boolean('can_forward')->default(false);
            $table->boolean('can_assign')->default(false);

            // For assign steps: the role.key of the role being assigned to
            $table->string('assigns_to_role_key', 50)->nullable();

            $table->string('action_label')->default('Proses');
            $table->string('reject_label')->nullable();

            // Auto-advance if no action taken within N hours (null = never)
            $table->unsignedSmallInteger('auto_advance_hours')->nullable();

            // Meaningful on requester_review steps: extra hours a rework gets
            // on top of whatever time was left on the original deadline (or
            // on top of "now" if it had already passed). Null = app default.
            $table->unsignedSmallInteger('rework_additional_hours')->nullable();

            $table->timestamps();

            $table->unique(['department_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
