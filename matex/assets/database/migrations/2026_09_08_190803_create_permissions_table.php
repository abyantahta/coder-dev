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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        $permissions = [
            ['key' => 'manage-locations', 'label' => 'Manage Lokasi'],
            ['key' => 'manage-departments', 'label' => 'Manage Department'],
            ['key' => 'manage-jabatan', 'label' => 'Manage Jabatan'],
            ['key' => 'manage-users', 'label' => 'Manage Users'],
            ['key' => 'manage-roles', 'label' => 'Manage Roles'],
        ];

        $now = now();
        foreach ($permissions as &$permission) {
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
        }
        unset($permission);

        DB::table('permissions')->insert($permissions);

        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $permissionIds = DB::table('permissions')->pluck('id');
            $pivotRows = $permissionIds->map(fn ($permissionId) => [
                'role_id' => $adminRole->id,
                'permission_id' => $permissionId,
            ])->all();

            DB::table('permission_role')->insert($pivotRows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
    }
};
