<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Titik awal bersih untuk model stok baru: stok jadi milik departemen
     * masing-masing (department_stocks), bukan lagi satu pool gudang pusat.
     * Qty lama di-reset ke 0 karena tidak punya atribusi departemen yang valid.
     */
    public function up(): void
    {
        DB::table('item_stocks')->update(['qty_on_hand' => 0, 'qty_reserved' => 0]);
        DB::table('department_stocks')->update(['qty_held' => 0]);
        DB::table('memo_stocks')->update(['qty_on_hand' => 0]);
    }

    public function down(): void
    {
        // Data lama sudah tidak bisa dikembalikan (reset destruktif by design).
    }
};
