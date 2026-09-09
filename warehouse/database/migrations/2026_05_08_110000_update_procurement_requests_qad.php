<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update status enum tambah sent_to_qad
        DB::statement("ALTER TABLE procurement_requests MODIFY status ENUM(
            'draft','pending_section','pending_manager','pending_director',
            'approved','sent_to_qad','rejected','completed'
        ) NOT NULL DEFAULT 'draft'");

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->string('qad_req_no', 50)->nullable()->after('reject_reason')->comment('No. Requisition di QAD');
            $table->timestamp('sent_to_qad_at')->nullable()->after('qad_req_no');
            $table->text('qad_sync_message')->nullable()->after('sent_to_qad_at');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropColumn(['qad_req_no', 'sent_to_qad_at', 'qad_sync_message']);
        });
    }
};
