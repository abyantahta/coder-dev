<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Raw SQL instead of ->change() — doctrine/dbal isn't installed.
        DB::statement('ALTER TABLE wo_part_order_lines MODIFY quantity INT UNSIGNED NOT NULL DEFAULT 1');

        Schema::table('wo_part_order_lines', function (Blueprint $table) {
            $table->dropColumn('needed_date');
        });

        Schema::table('wo_part_orders', function (Blueprint $table) {
            // Need date is now per PR (whole batch for a WO), not per line.
            $table->date('need_date')->nullable()->after('request_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE wo_part_order_lines MODIFY quantity DECIMAL(10,2) NOT NULL DEFAULT 1');

        Schema::table('wo_part_order_lines', function (Blueprint $table) {
            $table->date('needed_date')->nullable();
        });

        Schema::table('wo_part_orders', function (Blueprint $table) {
            $table->dropColumn('need_date');
        });
    }
};
