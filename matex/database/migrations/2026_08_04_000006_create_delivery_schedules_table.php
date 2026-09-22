<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ohp_supplier_id')->constrained('companies');
            $table->date('scheduled_date');
            $table->decimal('qty', 12, 3);
            $table->decimal('qty_confirmed', 12, 3)->nullable();
            $table->string('status')->default('planned');
            $table->timestamp('ship_confirmed_at')->nullable();
            $table->foreignId('ship_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_schedules');
    }
};
