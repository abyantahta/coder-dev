<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qad_items', function (Blueprint $table) {
            $table->string('uom', 20)->nullable()->after('description'); // t_pt_um
        });
    }

    public function down(): void
    {
        Schema::table('qad_items', function (Blueprint $table) {
            $table->dropColumn('uom');
        });
    }
};
