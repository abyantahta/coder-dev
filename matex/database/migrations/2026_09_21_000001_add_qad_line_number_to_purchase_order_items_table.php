<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            // Line number QAD assigned when the PO was pushed
            // (maintainPurchaseOrder) — needed later to tell QAD which line
            // a Receiving applies to (receivePurchaseOrder).
            $table->unsignedInteger('qad_line_number')->nullable()->after('qty_confirmed');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('qad_line_number');
        });
    }
};
