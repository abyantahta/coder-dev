<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori item ATK sebelumnya teks bebas per item (kolom `category`).
     * Dipindah jadi master data terkelola (`procurement_categories`) supaya
     * bisa di-manage & filter/pill di layar buat request tetap konsisten.
     */
    public function up(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')
                ->constrained('procurement_categories')->nullOnDelete();
        });

        $names = DB::table('procurement_items')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        foreach ($names as $name) {
            $id = DB::table('procurement_categories')->insertGetId([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('procurement_items')->where('category', $name)->update(['category_id' => $id]);
        }

        Schema::table('procurement_items', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('uom_id');
        });

        DB::statement('
            UPDATE procurement_items pi
            JOIN procurement_categories pc ON pc.id = pi.category_id
            SET pi.category = pc.name
        ');

        Schema::table('procurement_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
