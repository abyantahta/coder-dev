<?php

namespace Database\Seeders;

use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\DepartmentRole;
use App\Models\WoCategory;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        if (Department::where('slug', 'maintenance')->exists()) {
            $this->command?->info('Departemen sudah ada, DepartmentSeeder dilewati.');
            return;
        }

        // ── Maintenance (MTC) ─────────────────────────────────────────────────
        $mtc = Department::create([
            'name'               => 'Maintenance',
            'code'               => 'MTC',
            'slug'               => 'maintenance',
            'description'        => 'Departemen perawatan dan perbaikan mesin produksi',
            'color'              => 'blue',
            'is_active'          => true,
            'has_warehouse'      => true,
            'has_unit_structure' => true,
        ]);

        $mtcSH = DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Section Head',  'key' => 'section_head', 'is_superuser' => true,  'level' => 1, 'sort_order' => 1]);
        $mtcUH = DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Unit Head',     'key' => 'unit_head',    'is_superuser' => false, 'level' => 2, 'sort_order' => 2]);
        $mtcGH = DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Group Head',    'key' => 'group_head',   'is_superuser' => false, 'level' => 3, 'sort_order' => 3]);
               DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Teknisi',        'key' => 'member',       'is_superuser' => false, 'level' => 4, 'sort_order' => 4]);
               DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Warehouse MTC',  'key' => 'warehouse_mtc','is_superuser' => false, 'level' => 5, 'sort_order' => 5]);

        WoCategory::create(['department_id' => $mtc->id, 'name' => 'Safety',              'leadtime_days' => 1, 'color' => 'red',    'sort_order' => 1, 'description' => 'Keselamatan kerja — respons cepat']);
        WoCategory::create(['department_id' => $mtc->id, 'name' => 'Quality / Customer', 'leadtime_days' => 1, 'color' => 'orange', 'sort_order' => 2, 'description' => 'Isu kualitas / permintaan pelanggan']);
        WoCategory::create(['department_id' => $mtc->id, 'name' => 'Productivity',       'leadtime_days' => 2, 'color' => 'blue',   'sort_order' => 3, 'description' => 'Peningkatan produktivitas']);
        WoCategory::create(['department_id' => $mtc->id, 'name' => 'General / Additional','leadtime_days' => 3, 'color' => 'slate',  'sort_order' => 4, 'description' => 'Perbaikan umum dan tambahan']);

        // MTC Approval Steps
        // Step 1: Unit Head accepts / rejects / forwards
        ApprovalStep::create([
            'department_id' => $mtc->id, 'step_order' => 1,
            'name'          => 'Penerimaan WO',
            'actor_role_id' => $mtcUH->id,
            'step_type'     => 'standard',
            'can_reject'    => true,
            'can_forward'   => true,
            'action_label'  => 'Terima WO',
            'reject_label'  => 'Tolak WO',
        ]);
        // Step 2: Unit Head checks spare parts
        ApprovalStep::create([
            'department_id' => $mtc->id, 'step_order' => 2,
            'name'          => 'Pengecekan Sparepart',
            'actor_role_id' => $mtcUH->id,
            'step_type'     => 'spare_parts_check',
            'action_label'  => 'Simpan Data Sparepart',
        ]);
        // Step 3: Unit Head assigns group
        ApprovalStep::create([
            'department_id'      => $mtc->id, 'step_order' => 3,
            'name'               => 'Assign ke Group',
            'actor_role_id'      => $mtcUH->id,
            'step_type'          => 'assign',
            'can_assign'         => true,
            'assigns_to_role_key'=> 'group_head',
            'action_label'       => 'Assign ke Group Head',
        ]);
        // Step 4: Group Head assigns member
        ApprovalStep::create([
            'department_id'      => $mtc->id, 'step_order' => 4,
            'name'               => 'Assign ke Teknisi',
            'actor_role_id'      => $mtcGH->id,
            'step_type'          => 'assign',
            'can_assign'         => true,
            'assigns_to_role_key'=> 'member',
            'action_label'       => 'Assign ke Teknisi',
        ]);
        // Step 5: Teknisi marks completion
        ApprovalStep::create([
            'department_id' => $mtc->id, 'step_order' => 5,
            'name'          => 'Penyelesaian Pekerjaan',
            'actor_role_id' => null,
            'step_type'     => 'completion',
            'action_label'  => 'Tandai Selesai',
        ]);
        // Step 6: Requester reviews
        ApprovalStep::create([
            'department_id'      => $mtc->id, 'step_order' => 6,
            'name'               => 'Review oleh Requester',
            'actor_role_id'      => null,
            'step_type'          => 'requester_review',
            'can_reject'         => true,
            'action_label'       => 'Setujui Pekerjaan',
            'reject_label'       => 'Minta Rework',
            'auto_advance_hours' => 48,
        ]);

        // ── GA (placeholder) ──────────────────────────────────────────────────
        $ga = Department::create([
            'name'               => 'General Affairs',
            'code'               => 'GA',
            'slug'               => 'ga',
            'description'        => 'Departemen General Affairs (placeholder)',
            'color'              => 'green',
            'is_active'          => true,
            'has_warehouse'      => false,
            'has_unit_structure' => false,
        ]);

        $gaSH = DepartmentRole::create(['department_id' => $ga->id, 'name' => 'GA Section Head', 'key' => 'section_head', 'is_superuser' => true,  'level' => 1, 'sort_order' => 1]);
               DepartmentRole::create(['department_id' => $ga->id, 'name' => 'GA Staff',        'key' => 'staff',        'is_superuser' => false, 'level' => 2, 'sort_order' => 2]);

        WoCategory::create(['department_id' => $ga->id, 'name' => 'Umum',         'leadtime_days' => 3, 'color' => 'slate', 'sort_order' => 1, 'description' => 'Permintaan umum GA']);
        WoCategory::create(['department_id' => $ga->id, 'name' => 'Fasilitas',    'leadtime_days' => 2, 'color' => 'green', 'sort_order' => 2, 'description' => 'Pengelolaan fasilitas kantor']);

        ApprovalStep::create([
            'department_id' => $ga->id, 'step_order' => 1,
            'name'          => 'Penerimaan WO',
            'actor_role_id' => $gaSH->id,
            'step_type'     => 'standard',
            'can_reject'    => true,
            'can_forward'   => true,
            'action_label'  => 'Terima WO',
            'reject_label'  => 'Tolak WO',
        ]);
        ApprovalStep::create([
            'department_id'      => $ga->id, 'step_order' => 2,
            'name'               => 'Assign ke Staff',
            'actor_role_id'      => $gaSH->id,
            'step_type'          => 'assign',
            'can_assign'         => true,
            'assigns_to_role_key'=> 'staff',
            'action_label'       => 'Assign ke Staff GA',
        ]);
        // Staff itself checks material availability; leadtime starts here
        // (immediately if available, or once a self-ordered PR is received).
        ApprovalStep::create([
            'department_id' => $ga->id, 'step_order' => 3,
            'name'          => 'Pengecekan Material',
            'actor_role_id' => null,
            'step_type'     => 'material_check',
            'action_label'  => 'Material Tersedia',
        ]);
        ApprovalStep::create([
            'department_id' => $ga->id, 'step_order' => 4,
            'name'          => 'Penyelesaian Pekerjaan',
            'actor_role_id' => null,
            'step_type'     => 'completion',
            'action_label'  => 'Tandai Selesai',
        ]);
        ApprovalStep::create([
            'department_id'      => $ga->id, 'step_order' => 5,
            'name'               => 'Review oleh Requester',
            'actor_role_id'      => null,
            'step_type'          => 'requester_review',
            'can_reject'         => true,
            'action_label'       => 'Setujui Pekerjaan',
            'reject_label'       => 'Minta Rework',
            'auto_advance_hours' => 48,
        ]);

        // ── QA ────────────────────────────────────────────────────────────────
        $qa = Department::create([
            'name'               => 'Quality Assurance',
            'code'               => 'QA',
            'slug'               => 'qa',
            'description'        => 'Departemen Quality Assurance',
            'color'              => 'purple',
            'is_active'          => true,
            'has_warehouse'      => false,
            'has_unit_structure' => false,
        ]);

        $qaSH = DepartmentRole::create(['department_id' => $qa->id, 'name' => 'QA Section Head', 'key' => 'section_head', 'is_superuser' => true,  'level' => 1, 'sort_order' => 1]);
        $qaGH = DepartmentRole::create(['department_id' => $qa->id, 'name' => 'QA Group Head',   'key' => 'group_head',   'is_superuser' => false, 'level' => 2, 'sort_order' => 2]);
               DepartmentRole::create(['department_id' => $qa->id, 'name' => 'QA Member',       'key' => 'member',       'is_superuser' => false, 'level' => 3, 'sort_order' => 3]);

        WoCategory::create(['department_id' => $qa->id, 'name' => 'Inspeksi',      'leadtime_days' => 1, 'color' => 'purple', 'sort_order' => 1, 'description' => 'Inspeksi kualitas produk']);
        WoCategory::create(['department_id' => $qa->id, 'name' => 'Audit',         'leadtime_days' => 2, 'color' => 'blue',   'sort_order' => 2, 'description' => 'Audit internal / eksternal']);
        WoCategory::create(['department_id' => $qa->id, 'name' => 'Dokumentasi',   'leadtime_days' => 3, 'color' => 'slate',  'sort_order' => 3, 'description' => 'Pembuatan / revisi dokumen mutu']);

        // QA Approval Steps
        ApprovalStep::create([
            'department_id' => $qa->id, 'step_order' => 1,
            'name'          => 'Penerimaan WO',
            'actor_role_id' => $qaGH->id,
            'step_type'     => 'standard',
            'can_reject'    => true,
            'can_forward'   => true,
            'action_label'  => 'Terima WO',
            'reject_label'  => 'Tolak WO',
        ]);
        ApprovalStep::create([
            'department_id'      => $qa->id, 'step_order' => 2,
            'name'               => 'Assign ke Member QA',
            'actor_role_id'      => $qaGH->id,
            'step_type'          => 'assign',
            'can_assign'         => true,
            'assigns_to_role_key'=> 'member',
            'action_label'       => 'Assign ke QA Member',
        ]);
        ApprovalStep::create([
            'department_id' => $qa->id, 'step_order' => 3,
            'name'          => 'Penyelesaian Pekerjaan QA',
            'actor_role_id' => null,
            'step_type'     => 'completion',
            'action_label'  => 'Tandai Selesai',
        ]);
        ApprovalStep::create([
            'department_id'      => $qa->id, 'step_order' => 4,
            'name'               => 'Review oleh Requester',
            'actor_role_id'      => null,
            'step_type'          => 'requester_review',
            'can_reject'         => true,
            'action_label'       => 'Setujui Pekerjaan',
            'reject_label'       => 'Minta Rework',
            'auto_advance_hours' => 48,
        ]);
    }
}
