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
        Schema::create('net_book_values', function (Blueprint $table) {
            $table->id();
            $table->string('no_asset');
            $table->year('year')->nullable();
            $table->integer('month')->nullable();
            $table->float('net_book_value')->nullable();
            $table->integer('category_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('net_book_values');
    }
};
