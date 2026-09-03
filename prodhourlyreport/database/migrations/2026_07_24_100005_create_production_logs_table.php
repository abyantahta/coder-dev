<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();

            // Historical entries must survive master data edits, so these
            // foreign keys restrict deletion instead of cascading.
            $table->foreignId('line_id')->constrained('lines')->restrictOnDelete();
            $table->foreignId('product_model_id')->constrained('product_models')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->timestamp('logged_at');
            $table->unsignedInteger('total_production')->default(0);
            $table->unsignedInteger('total_reject')->default(0);
            $table->unsignedInteger('total_repair')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['line_id', 'logged_at']);
            $table->index(['product_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_logs');
    }
};
