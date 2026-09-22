<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cutoff_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cutoff_counter')->unique();
            $table->timestamps();
            $table->date('cutoff_date')->nullable();
            $table->float('sto_progress')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cutoff_histories');
    }
};
