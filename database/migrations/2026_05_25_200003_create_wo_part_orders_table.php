<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_part_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wo_id')->constrained('work_orders')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users');          // Unit Head
            $table->foreignId('handled_by')->nullable()->constrained('users'); // Warehouse-MTC
            $table->string('pr_number')->nullable();   // QAD PR Number
            $table->text('request_note')->nullable();  // UH note about what's needed
            $table->date('need_date')->nullable();      // one need date for the whole PR batch, not per line
            $table->text('warehouse_note')->nullable();
            // status: pending_warehouse → pr_created → received
            $table->string('status')->default('pending_warehouse');
            $table->date('pr_date')->nullable();
            $table->date('expected_arrival')->nullable(); // pr_date + 30 days
            $table->timestamp('received_at')->nullable();
            $table->text('qad_response')->nullable();   // JSON placeholder for QAD API response
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_part_orders');
    }
};
