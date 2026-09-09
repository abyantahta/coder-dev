<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahan budget khusus bulan berjalan. Baris ini spesifik per year+month,
     * jadi bulan baru = tidak ada row = otomatis kembali ke default_budget saja.
     */
    public function up(): void
    {
        Schema::create('item_department_budget_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('procurement_item_id')->constrained('procurement_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('added_by')->constrained('users');
            $table->timestamps();
            $table->index(['department_id', 'procurement_item_id', 'year', 'month'], 'idbt_dept_item_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_department_budget_topups');
    }
};
