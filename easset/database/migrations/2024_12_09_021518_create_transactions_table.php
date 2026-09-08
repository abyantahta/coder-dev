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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('updated_by')->nullable()->default(null)->constrained('users');
            $table->string('keterangan')->nullable();
            $table->foreignId('pic')->nullable()->default(null)->constrained('users');
            $table->foreignId('location_id')->nullable()->default(null)->constrained('locations');
            $table->string('kondisi');
            $table->boolean('isEditable')->default(true);
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('cutoff_counter');
            $table->foreign('cutoff_counter')->references('cutoff_counter')->on('cutoff_histories');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
