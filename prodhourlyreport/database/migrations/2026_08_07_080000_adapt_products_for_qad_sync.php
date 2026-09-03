<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Products arrive from QAD unassigned; models link them to a line later.
            $table->dropForeign(['product_model_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['product_model_id', 'name']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_model_id')->nullable()->change();
            $table->foreign('product_model_id')
                ->references('id')
                ->on('product_models')
                ->nullOnDelete();

            $table->string('part_number')->nullable()->after('code');
            $table->string('description')->nullable()->after('part_number');
            $table->string('category')->nullable()->after('description');
            $table->string('group')->nullable()->after('category');
            $table->string('location')->nullable()->after('group');
            $table->string('qad_status')->nullable()->after('location');

            $table->unique(['product_model_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_model_id']);
            $table->dropUnique(['product_model_id', 'name']);
            $table->dropColumn(['part_number', 'description', 'category', 'group', 'location', 'qad_status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_model_id')->nullable(false)->change();
            $table->foreign('product_model_id')
                ->references('id')
                ->on('product_models')
                ->cascadeOnDelete();
            $table->unique(['product_model_id', 'name']);
        });
    }
};
