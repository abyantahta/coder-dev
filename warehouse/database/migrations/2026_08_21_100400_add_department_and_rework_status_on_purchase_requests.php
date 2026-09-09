<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PR sekarang dibuat per-departemen (bukan gabungan lintas departemen), dan
     * GA mengirim ke QAD langsung saat agregasi — approval Direktur pindah jadi
     * konfirmasi SETELAH terkirim (bukan gate sebelum kirim). Kolom director_by/
     * director_at/director_notes/reject_reason dipakai ulang, cuma beda makna:
     * sekarang berarti "siapa/kapan konfirmasi" bukan "siapa/kapan approve".
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('source')
                ->constrained('departments')->nullOnDelete();
        });

        DB::statement("UPDATE purchase_requests SET status = 'sent_to_qad' WHERE status = 'approved'");
        DB::statement("UPDATE purchase_requests SET status = 'sent_to_qad' WHERE status = 'pending_director'");
        DB::statement("UPDATE purchase_requests SET status = 'director_flagged' WHERE status = 'rejected'");

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM(
            'draft','submitted','completed','cancelled',
            'sent_to_qad','send_failed','director_confirmed','director_flagged',
            'received','distributing'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE purchase_requests SET status = 'rejected' WHERE status = 'director_flagged'");
        DB::statement("UPDATE purchase_requests SET status = 'approved' WHERE status = 'director_confirmed'");
        DB::statement("UPDATE purchase_requests SET status = 'sent_to_qad' WHERE status = 'send_failed'");

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM(
            'draft','submitted','pending_director','approved','sent_to_qad',
            'received','distributing','completed','cancelled','rejected'
        ) NOT NULL DEFAULT 'draft'");

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
