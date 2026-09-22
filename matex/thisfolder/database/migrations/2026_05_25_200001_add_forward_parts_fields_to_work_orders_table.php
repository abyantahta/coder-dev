<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Forward to GA/QA
            $table->string('forwarded_to')->nullable()->after('destination');
            $table->text('forward_reason')->nullable()->after('forwarded_to');

            // Parts check timing
            $table->timestamp('assigned_group_at')->nullable()->after('accepted_at'); // when GH assigned (leadtime starts)
            $table->timestamp('parts_ready_at')->nullable()->after('assigned_group_at'); // when parts received from warehouse
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['forwarded_to', 'forward_reason', 'assigned_group_at', 'parts_ready_at']);
        });
    }
};
