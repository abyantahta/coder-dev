<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_logs', function (Blueprint $table) {
            // Generated client-side when an entry is queued offline, so a
            // retried sync doesn't create a duplicate row.
            $table->uuid('client_uuid')->nullable()->unique()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropColumn('client_uuid');
        });
    }
};
