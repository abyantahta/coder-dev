<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Dukung penerimaan barang bertahap (partial receipt) — PR yang qty
     * diterimanya belum penuh tetap butuh status sendiri ("partially_received")
     * supaya tetap muncul di antrian "siap diterima" untuk lanjut terima sisanya,
     * bukan langsung dianggap selesai ("distributing").
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM(
            'draft','submitted','completed','cancelled',
            'sent_to_qad','send_failed','director_confirmed','director_denied',
            'received','distributing','partially_received'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE purchase_requests SET status = 'director_confirmed' WHERE status = 'partially_received'");

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM(
            'draft','submitted','completed','cancelled',
            'sent_to_qad','send_failed','director_confirmed','director_denied',
            'received','distributing'
        ) NOT NULL DEFAULT 'draft'");
    }
};
