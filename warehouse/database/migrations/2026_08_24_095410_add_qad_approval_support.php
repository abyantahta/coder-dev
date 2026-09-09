<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Approve/Deny Direktur sekarang beneran push ke QAD (action maintainRequisitionApproval),
     * bukan cuma catatan lokal. `qad_approver_code` = identitas QAD dipakai user (Direktur)
     * saat approve/deny — beda dari department_qad_configs.approver_code (itu tujuan routing
     * saat PR dibuat, bukan identitas siapa yang approve).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('qad_approver_code', 30)->nullable()->after('phone')
                ->comment('User id QAD dipakai saat approve/deny requisition (khusus Direktur)');
        });

        DB::statement("UPDATE purchase_requests SET status = 'director_denied' WHERE status = 'director_flagged'");

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM(
            'draft','submitted','completed','cancelled',
            'sent_to_qad','send_failed','director_confirmed','director_denied',
            'received','distributing'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE purchase_requests SET status = 'director_flagged' WHERE status = 'director_denied'");

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM(
            'draft','submitted','completed','cancelled',
            'sent_to_qad','send_failed','director_confirmed','director_flagged',
            'received','distributing'
        ) NOT NULL DEFAULT 'draft'");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('qad_approver_code');
        });
    }
};
