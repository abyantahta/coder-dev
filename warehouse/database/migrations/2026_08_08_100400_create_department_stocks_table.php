<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->decimal('qty_held', 12, 2)->default(0)->comment('Qty sudah dialokasikan GA ke dept, menunggu diambil');
            $table->timestamps();
            $table->unique(['item_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_stocks');
    }
};
