<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('qty');
        });

        $notes = DB::table('delivery_notes')
            ->join('delivery_schedules', 'delivery_schedules.id', '=', 'delivery_notes.delivery_schedule_id')
            ->whereNull('delivery_notes.delivery_date')
            ->select('delivery_notes.id', 'delivery_schedules.scheduled_date')
            ->get();

        foreach ($notes as $note) {
            DB::table('delivery_notes')->where('id', $note->id)->update([
                'delivery_date' => $note->scheduled_date,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });
    }
};
