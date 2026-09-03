<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('lines')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);

            $table->string('qad_code')->nullable()->unique();
            $table->enum('source', ['manual', 'qad'])->default('manual');
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['line_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_models');
    }
};
