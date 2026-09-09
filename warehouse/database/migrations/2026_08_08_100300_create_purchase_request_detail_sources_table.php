<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_detail_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pr_detail_id')->constrained('purchase_request_details')->cascadeOnDelete();
            $table->foreignId('procurement_request_detail_id');
            $table->foreign('procurement_request_detail_id', 'prds_proc_req_detail_fk')
                ->references('id')->on('procurement_request_details')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments');
            $table->decimal('qty', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_detail_sources');
    }
};
