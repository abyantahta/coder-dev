<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('department_qad_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->unique()->constrained('departments')->cascadeOnDelete();
            $table->string('site_code', 20)->comment('rqmShip/rqmSite di requisition QAD');
            $table->string('buyer_code', 20)->nullable()->comment('routeToBuyer');
            $table->string('approver_code', 20)->nullable()->comment('routeToApr');
            $table->string('end_user_id', 20)->nullable()->comment('rqmEndUserid');
            $table->string('requester_userid', 20)->nullable()->comment('rqmRqbyUserid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_qad_configs');
    }
};
