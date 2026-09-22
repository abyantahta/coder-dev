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
        Schema::table('wo_part_orders', function (Blueprint $table) {
            // QAD's own ApprovalStatus code from SDI_getPRtoPO_, e.g. '2' = approved.
            $table->string('qad_approval_status')->nullable()->after('qad_po_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wo_part_orders', function (Blueprint $table) {
            $table->dropColumn('qad_approval_status');
        });
    }
};
