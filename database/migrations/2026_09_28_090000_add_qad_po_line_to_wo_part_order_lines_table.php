<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * QAD's own PO line number for this PR line. QAD numbers PO lines
     * independently of the PR (and restarts per PO on a split), and only
     * exposes it while the line is still open (SDI_getActivePO2) — so it is
     * captured the first time it is seen and kept from then on.
     */
    public function up(): void
    {
        Schema::table('wo_part_order_lines', function (Blueprint $table) {
            $table->unsignedInteger('qad_po_line')->nullable()->after('qad_po_no');
        });
    }

    public function down(): void
    {
        Schema::table('wo_part_order_lines', function (Blueprint $table) {
            $table->dropColumn('qad_po_line');
        });
    }
};
