<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivings', function (Blueprint $table) {
            // Was unique — blocked a second (partial) receipt against the
            // same DN entirely. A DN can now be received in installments,
            // so this is a regular index instead. MySQL needs the new index
            // created before the old unique one is dropped, since the FK on
            // this column always needs a covering index.
            $table->index('delivery_note_id', 'receivings_delivery_note_id_idx');
            $table->dropUnique(['delivery_note_id']);
        });
    }

    public function down(): void
    {
        Schema::table('receivings', function (Blueprint $table) {
            $table->unique('delivery_note_id', 'receivings_delivery_note_id_unique');
            $table->dropIndex('receivings_delivery_note_id_idx');
        });
    }
};
