<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether a department can be chosen as a WO destination. Departments
     * such as Produksi/Engineering exist so their users can SEND WOs, but
     * have no receiving flow. New departments default to "send only";
     * departments that already have an approval flow (MTC, GA, QA) keep
     * receiving WOs exactly as before.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('accepts_work_orders')->default(false)->after('is_active');
        });

        DB::table('departments')
            ->whereIn('id', DB::table('approval_steps')->select('department_id'))
            ->update(['accepts_work_orders' => true]);
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('accepts_work_orders');
        });
    }
};
