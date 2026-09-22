<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_rm_id')->constrained('companies')->cascadeOnDelete();
            $table->date('period_month');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['supplier_rm_id', 'period_month']);
        });

        Schema::create('forecast_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forecast_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_number');
            $table->string('description')->nullable();
            $table->unsignedInteger('qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_items');
        Schema::dropIfExists('forecasts');
    }
};
