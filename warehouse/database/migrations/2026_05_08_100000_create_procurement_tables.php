<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('procurement_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('name');
            $table->foreignId('uom_id')->constrained('uoms');
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('req_no', 30)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('purpose')->comment('Tujuan/keperluan permintaan');
            $table->enum('status', [
                'draft',
                'pending_section',
                'pending_manager',
                'pending_director',
                'approved',
                'rejected',
                'completed',
            ])->default('draft');
            $table->text('notes')->nullable();

            // Section approval
            $table->foreignId('section_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('section_at')->nullable();
            $table->text('section_notes')->nullable();

            // Manager approval
            $table->foreignId('manager_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_at')->nullable();
            $table->text('manager_notes')->nullable();

            // Director approval
            $table->foreignId('director_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('director_at')->nullable();
            $table->text('director_notes')->nullable();

            // Rejection
            $table->string('rejected_stage', 20)->nullable()->comment('section|manager|director');
            $table->text('reject_reason')->nullable();

            $table->date('req_date');
            $table->timestamps();
        });

        Schema::create('procurement_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('procurement_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('procurement_items');
            $table->decimal('qty', 12, 2);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_request_details');
        Schema::dropIfExists('procurement_requests');
        Schema::dropIfExists('procurement_items');
        Schema::dropIfExists('uoms');
    }
};
