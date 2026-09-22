<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wo_id')->constrained('work_orders')->onDelete('cascade');
            $table->string('part_number')->nullable(); // QAD material code
            $table->string('part_name');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('pcs'); // pcs, m, kg, liter, dll
            $table->boolean('is_available')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_spare_parts');
    }
};
