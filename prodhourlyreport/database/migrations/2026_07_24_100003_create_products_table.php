<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_model_id')->constrained('product_models')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);

            $table->string('qad_code')->nullable()->unique();
            $table->enum('source', ['manual', 'qad'])->default('manual');
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['product_model_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
