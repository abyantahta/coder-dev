<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('description');
            $table->string('unit', 20)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('warehouse', 20)->default('WH01');
            $table->string('photo')->nullable();
            $table->boolean('is_memo')->default(false)->comment('Item disimpan di luar inventory QAD');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('item_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('warehouse', 20)->default('WH01');
            $table->decimal('qty_on_hand', 12, 2)->default(0);
            $table->decimal('qty_reserved', 12, 2)->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->unique(['item_id', 'warehouse']);
        });

        Schema::create('memo_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty_on_hand', 12, 2)->default(0);
            $table->timestamp('last_updated')->nullable();
            $table->unique('item_id');
        });

        Schema::create('minimum_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('warehouse', 20)->default('WH01');
            $table->decimal('min_qty', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['item_id', 'warehouse']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minimum_stocks');
        Schema::dropIfExists('memo_stocks');
        Schema::dropIfExists('item_stocks');
        Schema::dropIfExists('items');
    }
};
