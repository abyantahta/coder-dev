<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->timestamp('planned_start_at')->nullable()->after('deadline');
            $table->timestamp('planned_end_at')->nullable()->after('planned_start_at');
            $table->timestamp('actual_start_at')->nullable()->after('planned_end_at');
            $table->timestamp('actual_end_at')->nullable()->after('actual_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['planned_start_at', 'planned_end_at', 'actual_start_at', 'actual_end_at']);
        });
    }
};
