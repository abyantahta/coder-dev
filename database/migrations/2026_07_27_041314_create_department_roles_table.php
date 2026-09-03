<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 50);
            $table->boolean('is_superuser')->default(false);
            $table->unsignedTinyInteger('level')->default(99);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['department_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_roles');
    }
};
