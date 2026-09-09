<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori item ATK jadi bertingkat (parent + sub-kategori) — dipakai buat
     * tree filter di layar "Buat Permintaan". 2 level cukup (parent tanpa
     * parent_id, sub-kategori punya parent_id) — belum perlu N-level.
     */
    public function up(): void
    {
        Schema::table('procurement_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')
                ->constrained('procurement_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
