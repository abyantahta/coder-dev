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
        Schema::table('wo_part_order_lines', function (Blueprint $table) {
            // A single PR can be split across multiple POs by QAD (e.g. by
            // vendor) — each PR line's own PONbr, from SDI_getPRtoPO_,
            // independent of wo_part_orders.qad_po_no (which only ever
            // held the first PO found).
            $table->string('qad_po_no')->nullable()->after('qad_item_id');
            $table->string('qad_po_status')->nullable()->after('qad_po_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wo_part_order_lines', function (Blueprint $table) {
            $table->dropColumn(['qad_po_no', 'qad_po_status']);
        });
    }
};
