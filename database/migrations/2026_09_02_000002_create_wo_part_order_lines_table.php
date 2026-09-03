<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_part_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wo_part_order_id')->constrained('wo_part_orders')->cascadeOnDelete();
            $table->foreignId('qad_item_id')->nullable()->constrained('qad_items')->nullOnDelete();
            $table->string('part_code')->nullable(); // snapshot of qad_items.qad_code at add-time; null for custom lines
            $table->string('description');           // copied from QadItem or freely typed
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('uom', 20)->default('PC');
            $table->date('needed_date')->nullable();
            $table->boolean('is_custom')->default(false); // true = manually typed, no matching qad_items row
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_part_order_lines');
    }
};
