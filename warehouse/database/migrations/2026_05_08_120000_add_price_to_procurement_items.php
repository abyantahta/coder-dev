<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->after('description')->comment('Harga dari QAD');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
