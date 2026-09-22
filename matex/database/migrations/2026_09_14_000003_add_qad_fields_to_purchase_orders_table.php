<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('qad_status')->nullable()->after('notes'); // pending | success | failed
            $table->string('qad_po_number')->nullable()->after('qad_status');
            $table->json('qad_payload')->nullable()->after('qad_po_number');
            $table->json('qad_response')->nullable()->after('qad_payload');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['qad_status', 'qad_po_number', 'qad_payload', 'qad_response']);
        });
    }
};
