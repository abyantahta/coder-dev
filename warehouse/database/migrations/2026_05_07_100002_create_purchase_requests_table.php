<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no', 30)->unique();
            $table->enum('status', ['draft', 'submitted', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pr_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty_needed', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('qad_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type', 50);
            $table->enum('status', ['success', 'failed', 'partial']);
            $table->integer('records_synced')->default(0);
            $table->text('message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('synced_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qad_sync_logs');
        Schema::dropIfExists('purchase_request_details');
        Schema::dropIfExists('purchase_requests');
    }
};
