<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_request_details', function (Blueprint $table) {
            $table->boolean('is_over_budget')->default(false)->after('notes')
                ->comment('Snapshot saat submit: qty x harga melebihi sisa budget bulan itu');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_request_details', function (Blueprint $table) {
            $table->dropColumn('is_over_budget');
        });
    }
};
