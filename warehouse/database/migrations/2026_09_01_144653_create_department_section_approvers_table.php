<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dukung 1 user section/kasi jadi approver buat LEBIH DARI 1 departemen
     * (mis. kasi merangkap PPIC & Produksi). `users.department_id` tetap
     * departemen "rumah" user itu (dipakai buat request dia sendiri, dll) —
     * tabel ini murni daftar tambahan departemen yang boleh dia approve.
     */
    public function up(): void
    {
        Schema::create('department_section_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_section_approvers');
    }
};
