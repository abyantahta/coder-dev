<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_department_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('procurement_item_id')->constrained('procurement_items')->cascadeOnDelete();
            $table->decimal('default_budget', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['department_id', 'procurement_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_department_budgets');
    }
};
