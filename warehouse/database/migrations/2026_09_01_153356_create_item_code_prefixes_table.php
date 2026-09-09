<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master prefix kode item (ATK, OEQ, ITM, dst) — dipilih saat tambah item,
     * sequence-nya jalan sendiri per-prefix (ATK-001, OEQ-001, dst — bukan
     * satu urutan gabungan semua "ATK-xxx" seperti sebelumnya).
     */
    public function up(): void
    {
        Schema::create('item_code_prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('label', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('item_code_prefixes')->insert([
            ['code' => 'ATK', 'label' => 'Alat Tulis Kantor', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'OEQ', 'label' => 'Office Equipment', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ITM', 'label' => 'IT Items', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('item_code_prefixes');
    }
};
