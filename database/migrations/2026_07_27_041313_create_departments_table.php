<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('blue');
            $table->boolean('is_active')->default(true);
            $table->boolean('has_warehouse')->default(false);
            $table->boolean('has_unit_structure')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
