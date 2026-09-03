<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('target_department_id')
                ->nullable()
                ->after('destination')
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('wo_category_id')
                ->nullable()
                ->after('target_department_id')
                ->constrained('wo_categories')
                ->nullOnDelete();

            $table->unsignedTinyInteger('current_step_order')
                ->nullable()
                ->after('wo_category_id')
                ->comment('Active step in approval chain (null = not yet started)');

            $table->unsignedTinyInteger('leadtime_days')
                ->nullable()
                ->after('current_step_order');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['target_department_id']);
            $table->dropForeign(['wo_category_id']);
            $table->dropColumn(['target_department_id', 'wo_category_id', 'current_step_order', 'leadtime_days']);
        });
    }
};
