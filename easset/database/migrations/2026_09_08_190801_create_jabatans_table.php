<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('jabatan_id')->nullable()->after('position')->constrained('jabatans');
        });

        // Migrate every distinct existing free-text position into a real
        // Jabatan row, and point each user at the matching one.
        $positions = DB::table('users')->whereNotNull('position')->distinct()->pluck('position');

        foreach ($positions as $position) {
            $jabatanId = DB::table('jabatans')->insertGetId([
                'name' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('position', $position)->update(['jabatan_id' => $jabatanId]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('jabatan_id');
        });

        DB::table('users')
            ->join('jabatans', 'jabatans.id', '=', 'users.jabatan_id')
            ->update(['users.position' => DB::raw('jabatans.name')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jabatan_id');
        });

        Schema::dropIfExists('jabatans');
    }
};
