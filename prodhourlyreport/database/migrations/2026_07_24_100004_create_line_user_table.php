<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Assigns which lines a leader / group head is allowed to work with.
        // Unit head and above are not scoped here — they see every line.
        Schema::create('line_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('line_id')->constrained('lines')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_user');
    }
};
