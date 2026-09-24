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
        // Lets a WoPartOrder exist without a parent WorkOrder — a
        // standalone PR/PO that Warehouse starts on its own, not derived
        // from any WO's material-check step. department_id/title carry
        // what a WO would otherwise have supplied (target department,
        // display title) for that case; see WoPartOrder::targetDepartmentId()
        // and ::displayTitle().
        Schema::table('wo_part_orders', function (Blueprint $table) {
            $table->dropForeign(['wo_id']);
        });

        DB::statement('ALTER TABLE wo_part_orders MODIFY wo_id BIGINT UNSIGNED NULL');

        Schema::table('wo_part_orders', function (Blueprint $table) {
            $table->foreign('wo_id')->references('id')->on('work_orders')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->after('wo_id')->constrained('departments')->nullOnDelete();
            $table->string('title')->nullable()->after('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wo_part_orders', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'title']);
            $table->dropForeign(['wo_id']);
        });

        DB::statement('ALTER TABLE wo_part_orders MODIFY wo_id BIGINT UNSIGNED NOT NULL');

        Schema::table('wo_part_orders', function (Blueprint $table) {
            $table->foreign('wo_id')->references('id')->on('work_orders')->cascadeOnDelete();
        });
    }
};
