<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentRole;
use App\Models\MaintenanceGroup;
use App\Models\MaintenanceUnit;
use App\Models\User;
use App\Models\WoCategory;
use App\Models\WoPartOrder;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private Department $mtc;
    private Department $qa;
    private Department $ga;
    private array $mtcRoles = [];
    private array $qaRoles  = [];
    private array $gaRoles  = [];
    private array $mtcCats  = [];
    private array $qaCats   = [];
    private array $gaCats   = [];

    public function run(): void
    {
        if (User::where('email', 'deni@sankei.com')->exists()) {
            $this->command?->warn('Data demo user/WO sudah ada. Dilewati. Pakai `php artisan migrate:fresh --seed` jika ingin reset dari nol.');
            return;
        }

        if (! Department::where('slug', 'maintenance')->exists()) {
            $this->call(DepartmentSeeder::class);
        }

        $this->loadDeptData();

        // ── Unit & Groups (Manufacturing) ────────────────────────────────────
        $unitManufacturing = MaintenanceUnit::create(['name' => 'Manufacturing', 'description' => 'Perawatan dan perbaikan mesin produksi']);
        $groupA = MaintenanceGroup::create(['name' => 'Group A', 'unit_id' => $unitManufacturing->id]);
        $groupB = MaintenanceGroup::create(['name' => 'Group B', 'unit_id' => $unitManufacturing->id]);

        // ── MTC Users ────────────────────────────────────────────────────────
        $sectionHead = User::create([
            'name' => 'Deni', 'email' => 'deni@sankei.com',
            'password' => Hash::make('password'), 'role' => 'section_head', 'department' => 'Maintenance',
            'department_id' => $this->mtc->id, 'dept_role_id' => $this->mtcRoles['section_head'],
        ]);

        $uhManufacturing = User::create([
            'name' => 'Wawan', 'email' => 'wawan@sankei.com',
            'password' => Hash::make('password'), 'role' => 'unit_head',
            'department' => 'Maintenance', 'unit_id' => $unitManufacturing->id,
            'department_id' => $this->mtc->id, 'dept_role_id' => $this->mtcRoles['unit_head'],
        ]);

        $ghA = User::create([
            'name' => 'Rochmat', 'email' => 'rochmat@sankei.com',
            'password' => Hash::make('password'), 'role' => 'group_head',
            'department' => 'Maintenance', 'unit_id' => $unitManufacturing->id, 'group_id' => $groupA->id,
            'department_id' => $this->mtc->id, 'dept_role_id' => $this->mtcRoles['group_head'],
        ]);

        $ghB = User::create([
            'name' => 'Aryo', 'email' => 'aryo@sankei.com',
            'password' => Hash::make('password'), 'role' => 'group_head',
            'department' => 'Maintenance', 'unit_id' => $unitManufacturing->id, 'group_id' => $groupB->id,
            'department_id' => $this->mtc->id, 'dept_role_id' => $this->mtcRoles['group_head'],
        ]);

        $memberAskiya = $this->mkMember('Askiya', 'askiya@sankei.com', $unitManufacturing, $groupB);
        $memberBudi   = $this->mkMember('Budi',   'budi@sankei.com',   $unitManufacturing, $groupA);
        $memberPajar  = $this->mkMember('Pajar',  'pajar@sankei.com',  $unitManufacturing, $groupA);
        $memberIrwan  = $this->mkMember('Irwan',  'irwan@sankei.com',  $unitManufacturing, $groupA);
        $memberIwan   = $this->mkMember('Iwan',   'iwan@sankei.com',   $unitManufacturing, $groupB);
        $memberWisnu  = $this->mkMember('Wisnu',  'wisnu@sankei.com',  $unitManufacturing, $groupB);

        // Section Head also handles warehouse PR in demo data
        $warehouse = $sectionHead;

        // ── QA Users ─────────────────────────────────────────────────────────
        User::create([
            'name' => 'Haekal', 'email' => 'haekal@sankei.com',
            'password' => Hash::make('password'), 'role' => 'qa_section_head', 'department' => 'QA',
            'department_id' => $this->qa->id, 'dept_role_id' => $this->qaRoles['section_head'],
        ]);
        $qaGH = User::create([
            'name' => 'Iqbal', 'email' => 'iqbal@sankei.com',
            'password' => Hash::make('password'), 'role' => 'qa_group_head', 'department' => 'QA',
            'department_id' => $this->qa->id, 'dept_role_id' => $this->qaRoles['group_head'],
        ]);
        $qaMember1 = $this->mkQaMember('Acil',  'acil@sankei.com');
        $qaMember2 = $this->mkQaMember('Telur', 'telur@sankei.com');

        // ── GA Users ─────────────────────────────────────────────────────────
        $gaSectionHead = User::create([
            'name' => 'Herlan', 'email' => 'herlan@sankei.com',
            'password' => Hash::make('password'), 'role' => 'ga_section_head', 'department' => 'GA',
            'department_id' => $this->ga->id, 'dept_role_id' => $this->gaRoles['section_head'],
        ]);
        $gaStaff1 = $this->mkGaStaff('Sarja', 'sarja@sankei.com');
        $gaStaff2 = $gaStaff1;

        // ── IT Superadmin ─────────────────────────────────────────────────────
        $itAdmin = User::create([
            'name'          => 'IT Admin',
            'email'         => 'it@sankei-dharma.com',
            'password'      => Hash::make('password'),
            'role'          => 'section_head',
            'department'    => 'IT',
            'is_superadmin' => true,
        ]);

        // Requester demo: staf lintas dept (semua user bisa buat WO, termasuk ke dept sendiri)
        $userProduksi = $memberBudi;
        $userQC       = $qaMember1;
        $userIT       = $itAdmin;

        // ── MTC Sample WOs ───────────────────────────────────────────────────
        $catProductivity = $this->mtcCats['Productivity'];
        $catGeneral      = $this->mtcCats['General / Additional'];
        $catQuality      = $this->mtcCats['Quality / Customer'];
        $catSafety       = $this->mtcCats['Safety'];

        $this->finishedWO($userProduksi, 'Perbaikan Motor Konveyor Line 2',
            'Motor konveyor pada line 2 mengeluarkan suara aneh.', 'Mechanical', 'high',
            $unitManufacturing, $groupA, $uhManufacturing, $ghA, $memberBudi, 10, 3, 0, 100, $userProduksi, $catProductivity);

        $this->finishedWO($userQC, 'Instalasi Lampu Panel QC',
            'Lampu panel area QC mati semua, perlu instalasi ulang.', 'Electrical', 'medium',
            $unitManufacturing, $groupA, $uhManufacturing, $ghA, $memberIrwan, 15, 5, 1, 85, $userQC, $catGeneral);

        $this->finishedWO($userIT, 'Perbaikan AC Server Room',
            'AC server room tidak dingin, suhu sudah 35°C.', 'Electrical', 'urgent',
            $unitManufacturing, $groupB, $uhManufacturing, $ghB, $memberWisnu, 20, 9, 0, 80, $userIT, $catGeneral);

        $this->finishedWO($userProduksi, 'Ganti Bearing Mesin Press',
            'Bearing mesin press unit 3 bunyi dan perlu diganti.', 'Mechanical', 'high',
            $unitManufacturing, $groupA, $uhManufacturing, $ghA, $memberPajar, 8, 6, 0, 100, $userProduksi, $catProductivity);

        $this->finishedWO($userQC, 'Perbaikan Sensor Suhu Oven',
            'Sensor suhu oven curing tidak akurat.', 'Electrical', 'medium',
            $unitManufacturing, $groupA, $uhManufacturing, $ghA, $memberBudi, 25, 10, 1, 55, $userQC, $catQuality);

        $this->finishedWO($userIT, 'Overhaul Kompresor',
            'Kompresor ruang produksi tekanannya drop.', 'Mechanical', 'high',
            $unitManufacturing, $groupB, $uhManufacturing, $ghB, $memberAskiya, 12, 5, 0, 100, $userIT, $catProductivity);

        // WO7: Active — assigned to member
        $wo7 = $this->makeWO($userProduksi, 'Cek dan Kalibrasi Panel PLC', 'PLC line 1 sering error.', 'Electrical', 'high', $catProductivity);
        $a7 = now()->subDays(3);
        $wo7->update([
            'status' => 'assigned_member', 'unit_id' => $unitManufacturing->id,
            'assigned_group_id' => $groupA->id, 'assigned_member_id' => $memberIrwan->id,
            'accepted_by' => $uhManufacturing->id, 'accepted_at' => $a7, 'deadline' => $a7->copy()->addDays(7),
            'planned_start_at' => $a7, 'planned_end_at' => $a7->copy()->addDays(7),
            'actual_start_at' => $a7,
            'current_step_order' => 5,
        ]);
        $wo7->addHistory($userProduksi->id, 'created', 'WO dibuat.');
        $wo7->addHistory($uhManufacturing->id, 'accepted', 'WO diterima.');
        $wo7->addHistory($ghA->id, 'assigned_member', "Diassign ke {$memberIrwan->name}.");

        // WO8: Pending
        $wo8 = $this->makeWO($userQC, 'Perbaikan Exhaust Fan Ruang QC', 'Exhaust fan area QC mati.', 'Mechanical', 'medium', $catGeneral);
        $wo8->addHistory($userQC->id, 'created', 'WO dibuat.');

        // WO9: Completed (waiting review)
        $wo9 = $this->makeWO($userIT, 'Ganti Kabel Ground Panel Listrik', 'Kabel ground panel utama sudah korosi.', 'Electrical', 'high', $catSafety);
        $a9 = now()->subDays(5);
        $wo9->update([
            'status' => 'completed', 'unit_id' => $unitManufacturing->id,
            'assigned_group_id' => $groupB->id, 'assigned_member_id' => $memberWisnu->id,
            'accepted_by' => $uhManufacturing->id, 'accepted_at' => $a9, 'deadline' => $a9->copy()->addDays(7),
            'planned_start_at' => $a9, 'planned_end_at' => $a9->copy()->addDays(7),
            'actual_start_at' => $a9, 'actual_end_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2),
            'current_step_order' => 6,
        ]);
        $wo9->addHistory($userIT->id, 'created', 'WO dibuat.');
        $wo9->addHistory($uhManufacturing->id, 'accepted', 'Diterima.');
        $wo9->addHistory($ghB->id, 'assigned_member', "Diassign ke {$memberWisnu->name}.");
        $wo9->addHistory($memberWisnu->id, 'completed', 'Pekerjaan selesai, menunggu review.');

        // WO10: Rework
        $wo10 = $this->makeWO($userProduksi, 'Perbaikan Pompa Hidrolik Press B', 'Pompa hidrolik press B bocor.', 'Mechanical', 'urgent', $catProductivity);
        $a10 = now()->subDays(6);
        $wo10->update([
            'status' => 'rework', 'unit_id' => $unitManufacturing->id,
            'assigned_group_id' => $groupA->id, 'assigned_member_id' => $memberPajar->id,
            'accepted_by' => $uhManufacturing->id, 'accepted_at' => $a10,
            'planned_start_at' => $a10, 'planned_end_at' => $a10->copy()->addDays(7),
            'actual_start_at' => $a10, 'actual_end_at' => null,
            'deadline' => now()->addDays(2),
            'rework_count' => 1,
            'rework_requested_at' => now()->subHours(12), 'rework_deadline' => now()->addDays(2),
            'review_note' => 'Masih ada rembesan, mohon dicek ulang.',
            'current_step_order' => 5,
        ]);
        $wo10->addHistory($userProduksi->id, 'created', 'WO dibuat.');
        $wo10->addHistory($uhManufacturing->id, 'accepted', 'Diterima.');
        $wo10->addHistory($ghA->id, 'assigned_member', "Diassign ke {$memberPajar->name}.");
        $wo10->addHistory($memberPajar->id, 'completed', 'Pekerjaan selesai.');
        $wo10->addHistory($userProduksi->id, 'rework', 'Masih ada rembesan, mohon dicek ulang.');

        // WO11: pending_parts
        $wo11 = $this->makeWO($userProduksi, 'Ganti Belt Conveyor Line 3', 'Belt conveyor line 3 sudah sobek.', 'Mechanical', 'high', $catProductivity);
        $wo11->update([
            'status' => 'pending_parts', 'unit_id' => $unitManufacturing->id,
            'accepted_by' => $uhManufacturing->id, 'accepted_at' => now()->subDays(2),
            'current_step_order' => 2,
        ]);
        $wo11->spareParts()->createMany([
            ['part_name' => 'Belt V-Type B120',   'part_number' => 'VB-B120',  'quantity' => 3, 'unit' => 'pcs', 'is_available' => false],
            ['part_name' => 'Bearing 6205ZZ',     'part_number' => 'BRG-6205', 'quantity' => 2, 'unit' => 'pcs', 'is_available' => false],
            ['part_name' => 'Grease Mobilux EP2', 'part_number' => null,        'quantity' => 1, 'unit' => 'kg',  'is_available' => true],
        ]);
        WoPartOrder::create([
            'wo_id' => $wo11->id, 'requested_by' => $uhManufacturing->id,
            'request_note' => 'Belt V-Type B120 dan Bearing 6205ZZ tidak tersedia di stok lokal.',
            'status' => 'pending_warehouse',
        ]);
        $wo11->addHistory($userProduksi->id, 'created', 'WO dibuat.');
        $wo11->addHistory($uhManufacturing->id, 'accepted', 'WO diterima.');
        $wo11->addHistory($uhManufacturing->id, 'pending_parts', 'Ada sparepart tidak tersedia. Diteruskan ke Warehouse-MTC.');

        // WO12: parts_ordered
        $wo12 = $this->makeWO($userQC, 'Perbaikan Panel Kontrol Oven Curing', 'Panel kontrol oven curing tidak merespon.', 'Electrical', 'medium', $catProductivity);
        $wo12->update([
            'status' => 'parts_ordered', 'unit_id' => $unitManufacturing->id,
            'accepted_by' => $uhManufacturing->id, 'accepted_at' => now()->subDays(12),
            'current_step_order' => 2,
        ]);
        $wo12->spareParts()->createMany([
            ['part_name' => 'Relay Omron MY4N',  'part_number' => 'OMRN-MY4N', 'quantity' => 4, 'unit' => 'pcs', 'is_available' => false],
            ['part_name' => 'MCB 20A Schneider', 'part_number' => 'MCB-20A',   'quantity' => 2, 'unit' => 'pcs', 'is_available' => false],
        ]);
        WoPartOrder::create([
            'wo_id' => $wo12->id, 'requested_by' => $uhManufacturing->id, 'handled_by' => $warehouse->id,
            'pr_number' => 'PR-2026-0012', 'status' => 'pr_created',
            'request_note' => 'Relay Omron dan MCB tidak ada di gudang.',
            'warehouse_note' => 'PR sudah dibuat di QAD. Supplier Omron, estimasi 3 minggu.',
            'pr_date' => now()->subDays(7)->toDateString(),
            'expected_arrival' => now()->addDays(23)->toDateString(),
        ]);
        $wo12->addHistory($userQC->id, 'created', 'WO dibuat.');
        $wo12->addHistory($uhManufacturing->id, 'accepted', 'WO diterima.');
        $wo12->addHistory($uhManufacturing->id, 'pending_parts', 'Sparepart tidak tersedia. Diteruskan ke Warehouse-MTC.');
        $wo12->addHistory($warehouse->id, 'parts_ordered', 'PR dibuat: PR-2026-0012. Estimasi tiba: '.now()->addDays(23)->format('d M Y').'.');

        // WO13: parts_received
        $wo13 = $this->makeWO($userIT, 'Overhaul Pompa Sirkulasi Cooling Tower', 'Pompa sirkulasi cooling tower getaran berlebih.', 'Mechanical', 'urgent', $catProductivity);
        $wo13->update([
            'status' => 'parts_received', 'unit_id' => $unitManufacturing->id,
            'accepted_by' => $uhManufacturing->id, 'accepted_at' => now()->subDays(35),
            'current_step_order' => 3,
        ]);
        $wo13->spareParts()->createMany([
            ['part_name' => 'Mechanical Seal 30mm', 'part_number' => 'MS-30',   'quantity' => 1, 'unit' => 'set', 'is_available' => false],
            ['part_name' => 'Impeller 150mm',       'part_number' => 'IMP-150', 'quantity' => 1, 'unit' => 'pcs', 'is_available' => false],
        ]);
        WoPartOrder::create([
            'wo_id' => $wo13->id, 'requested_by' => $uhManufacturing->id, 'handled_by' => $warehouse->id,
            'pr_number' => 'PR-2026-0008', 'status' => 'received',
            'request_note' => 'Mechanical seal dan impeller tidak ada di stok.',
            'warehouse_note' => 'Barang sudah tiba dan diterima. Kondisi sesuai spesifikasi.',
            'pr_date' => now()->subDays(30)->toDateString(),
            'expected_arrival' => now()->subDays(1)->toDateString(),
            'received_at' => now()->subDays(2),
        ]);
        $wo13->addHistory($userIT->id, 'created', 'WO dibuat.');
        $wo13->addHistory($uhManufacturing->id, 'accepted', 'WO diterima.');
        $wo13->addHistory($uhManufacturing->id, 'pending_parts', 'Sparepart tidak tersedia. Diteruskan ke Warehouse-MTC.');
        $wo13->addHistory($warehouse->id, 'parts_ordered', 'PR dibuat: PR-2026-0008.');
        $wo13->addHistory($warehouse->id, 'parts_received', 'Sparepart telah diterima. Menunggu Unit Head assign ke Group Head.');

        // Historical procurement data
        $this->historyProcurement($userProduksi, $uhManufacturing, $warehouse, 'PR-2026-0001', 60,  35, 'Ganti Seal Pompa Hidrolik Press A', 'Mechanical', 'medium', $unitManufacturing, $catProductivity);
        $this->historyProcurement($userQC,       $uhManufacturing, $warehouse, 'PR-2026-0003', 90,  25, 'Relay Kontrol Panel Produksi',      'Electrical', 'medium', $unitManufacturing, $catProductivity);
        $this->historyProcurement($userIT,       $uhManufacturing, $warehouse, 'PR-2026-0005', 120, 38, 'Vbelt Mesin Centrifuge Lab',        'Mechanical', 'low',    $unitManufacturing, $catGeneral);
        
        // ── QA Sample WOs ──────────────────────────────────────────────────────
        $catInspeksi    = $this->qaCats['Inspeksi'];
        $catAudit       = $this->qaCats['Audit'];
        $catDokumentasi = $this->qaCats['Dokumentasi'];

        $this->finishedQaWO($userProduksi, 'Audit Produk Batch #20260510',         'Audit kualitas produk batch 20260510 line 2.', 'high',   $qaGH, $qaMember1, 15,  2, 0, 100, $userProduksi, $catInspeksi);
        $this->finishedQaWO($userQC,       'Pemeriksaan Dokumen ISO 9001 Q1',      'Review dan pemeriksaan dokumen ISO 9001 Q1.',  'medium', $qaGH, $qaMember2, 20,  4, 1, 75,  $userQC,       $catDokumentasi);
        $this->finishedQaWO($userIT,       'Inspeksi Alat Ukur Kalibrasi Lab',     'Inspeksi dan kalibrasi alat ukur lab QC.',    'medium', $qaGH, $qaMember1, 10,  2, 0, 95,  $userIT,       $catInspeksi);

        // QA-WO4: Pending
        $wo_qa4 = $this->makeQaWO($userProduksi, 'Inspeksi Incoming Material Plastik', 'Material plastik dari supplier XYZ perlu diinspeksi.', 'medium', $catInspeksi);
        $wo_qa4->addHistory($userProduksi->id, 'created', 'WO dibuat.');

        // QA-WO5: Accepted
        $wo_qa5 = $this->makeQaWO($userQC, 'Audit Internal Prosedur Welding', 'Audit prosedur welding sesuai WPS revisi terbaru.', 'high', $catAudit);
        $wo_qa5->update(['status' => 'accepted', 'accepted_by' => $qaGH->id, 'accepted_at' => now()->subHours(3), 'current_step_order' => 2]);
        $wo_qa5->addHistory($userQC->id, 'created', 'WO dibuat.');
        $wo_qa5->addHistory($qaGH->id, 'accepted', 'WO diterima oleh QA.');

        // QA-WO6: Assigned to member
        $wo_qa6 = $this->makeQaWO($userProduksi, 'Evaluasi Produk NCR-2026-042', 'Evaluasi nonconformance report produk cacat dari line 1.', 'urgent', $catInspeksi);
        $deadline6 = now()->addDays(2);
        $wo_qa6->update([
            'status' => 'assigned_member', 'accepted_by' => $qaGH->id,
            'accepted_at' => now()->subDays(1), 'assigned_member_id' => $qaMember2->id,
            'deadline' => $deadline6,
            'planned_start_at' => now()->subDays(1), 'planned_end_at' => $deadline6,
            'actual_start_at' => now()->subDays(1), 'current_step_order' => 3,
        ]);
        $wo_qa6->addHistory($userProduksi->id, 'created', 'WO dibuat.');
        $wo_qa6->addHistory($qaGH->id, 'accepted', 'WO diterima.');
        $wo_qa6->addHistory($qaGH->id, 'assigned_member', "Diassign ke {$qaMember2->name}. Deadline: {$deadline6->format('d M Y')}.");

        // QA-WO7: Completed — menunggu review
        $wo_qa7 = $this->makeQaWO($userIT, 'Review SOP Penanganan Material Berbahaya', 'Review dan update SOP penanganan bahan kimia berbahaya.', 'medium', $catDokumentasi);
        $wo_qa7->update([
            'status' => 'completed', 'accepted_by' => $qaGH->id,
            'accepted_at' => now()->subDays(4), 'assigned_member_id' => $qaMember1->id,
            'deadline' => now()->subDays(1), 'completed_at' => now()->subHours(12),
            'planned_start_at' => now()->subDays(4), 'planned_end_at' => now()->subDays(1),
            'actual_start_at' => now()->subDays(4), 'actual_end_at' => now()->subHours(12),
            'current_step_order' => 4,
        ]);
        $wo_qa7->addHistory($userIT->id, 'created', 'WO dibuat.');
        $wo_qa7->addHistory($qaGH->id, 'accepted', 'WO diterima.');
        $wo_qa7->addHistory($qaGH->id, 'assigned_member', "Diassign ke {$qaMember1->name}.");
        $wo_qa7->addHistory($qaMember1->id, 'completed', 'Pekerjaan selesai. Requester wajib konfirmasi dalam 2 hari.');

        // QA-WO8: Rework
        $wo_qa8 = $this->makeQaWO($userProduksi, 'Verifikasi Produk Return Batch #20260502', 'Verifikasi kualitas produk return dari distributor.', 'high', $catInspeksi);
        $wo_qa8->update([
            'status' => 'rework', 'accepted_by' => $qaGH->id,
            'accepted_at' => now()->subDays(5), 'assigned_member_id' => $qaMember2->id,
            'planned_start_at' => now()->subDays(5), 'planned_end_at' => now()->addDay(),
            'actual_start_at' => now()->subDays(5), 'actual_end_at' => null,
            'deadline' => now()->addDays(2),
            'rework_count' => 1, 'rework_requested_at' => now()->subHours(6),
            'rework_deadline' => now()->addDays(2),
            'review_note' => 'Dokumentasi foto produk belum lengkap.',
            'current_step_order' => 3,
        ]);
        $wo_qa8->addHistory($userProduksi->id, 'created', 'WO dibuat.');
        $wo_qa8->addHistory($qaGH->id, 'accepted', 'WO diterima.');
        $wo_qa8->addHistory($qaGH->id, 'assigned_member', "Diassign ke {$qaMember2->name}.");
        $wo_qa8->addHistory($qaMember2->id, 'completed', 'Verifikasi selesai.');
        $wo_qa8->addHistory($userProduksi->id, 'rework', 'Dokumentasi foto produk belum lengkap.');

        // Historical QA WOs
        $this->finishedQaWO($userQC,       'Audit Mutu Supplier Baru',                 'Audit kualitas supplier baru.',                    'medium', $qaGH, $qaMember1, 90,  2, 0, 100, $userQC,       $catAudit);
        $this->finishedQaWO($userProduksi, 'Inspeksi Produk Ekspor Batch #20260201',   'Inspeksi kualitas produk batch ekspor.',            'high',   $qaGH, $qaMember2, 75,  4, 1, 80,  $userProduksi, $catInspeksi);
        $this->finishedQaWO($userIT,       'Review Prosedur Kalibrasi Lab Q4',         'Review prosedur kalibrasi triwulan 4.',             'low',    $qaGH, $qaMember1, 110, 3, 0, 90,  $userIT,       $catDokumentasi);

        $catUmum      = $this->gaCats['Umum'];
        $catFasilitas = $this->gaCats['Fasilitas'];

        // GA-WO1: Pending
        $wo_ga1 = $this->makeGaWO($userProduksi, 'Pengadaan ATK Departemen Produksi', 'Stok ATK habis, perlu pengadaan ulang.', 'low', $catUmum);
        $wo_ga1->addHistory($userProduksi->id, 'created', 'WO dibuat.');

        // GA-WO2: Accepted — belum diassign ke staff
        $wo_ga2 = $this->makeGaWO($userQC, 'Perbaikan AC Ruang Meeting', 'AC ruang meeting lantai 2 tidak dingin.', 'medium', $catFasilitas);
        $wo_ga2->update(['status' => 'accepted', 'accepted_by' => $gaSectionHead->id, 'accepted_at' => now()->subHours(4), 'current_step_order' => 2]);
        $wo_ga2->addHistory($userQC->id, 'created', 'WO dibuat.');
        $wo_ga2->addHistory($gaSectionHead->id, 'accepted', 'WO diterima.');

        // GA-WO3: Assigned to staff — menunggu pengecekan material (jadwal sudah ditentukan)
        $wo_ga3 = $this->makeGaWO($userIT, 'Pengadaan Meja Kerja Baru', 'Perlu 3 meja kerja baru untuk staff IT.', 'low', $catUmum);
        $start3 = now()->addDay()->setTime(8, 0);
        $wo_ga3->update([
            'status' => 'assigned_member', 'accepted_by' => $gaSectionHead->id,
            'accepted_at' => now()->subDays(1), 'assigned_member_id' => $gaStaff1->id,
            'scheduled_start_at' => $start3, 'deadline' => $start3->copy()->addDays(3),
            'planned_start_at' => $start3, 'planned_end_at' => $start3->copy()->addDays(3),
            'actual_start_at' => $start3,
            'current_step_order' => 3,
        ]);
        $wo_ga3->addHistory($userIT->id, 'created', 'WO dibuat.');
        $wo_ga3->addHistory($gaSectionHead->id, 'accepted', 'WO diterima.');
        $wo_ga3->addHistory($gaSectionHead->id, 'assigned_member', "Diassign ke {$gaStaff1->name}. Jadwal: {$start3->format('d M Y, H:i')} — {$wo_ga3->deadline->format('d M Y, H:i')}.");

        // GA-WO4: Material tersedia — leadtime sudah mulai, sedang dikerjakan
        $wo_ga4 = $this->makeGaWO($userProduksi, 'Perbaikan Pintu Gudang', 'Pintu gudang bahan baku macet.', 'medium', $catFasilitas);
        $accepted4 = now()->subDays(2);
        $checked4  = now()->subDays(1);
        $wo_ga4->update([
            'status' => 'assigned_member', 'accepted_by' => $gaSectionHead->id,
            'accepted_at' => $accepted4, 'assigned_member_id' => $gaStaff2->id,
            'deadline' => $checked4->copy()->addDays(2),
            'planned_start_at' => $checked4, 'planned_end_at' => $checked4->copy()->addDays(2),
            'actual_start_at' => $checked4,
            'current_step_order' => 4,
        ]);
        $wo_ga4->addHistory($userProduksi->id, 'created', 'WO dibuat.');
        $wo_ga4->addHistory($gaSectionHead->id, 'accepted', 'WO diterima.');
        $wo_ga4->addHistory($gaSectionHead->id, 'assigned_member', "Diassign ke {$gaStaff2->name}. Leadtime akan mulai setelah pengecekan material.");
        $wo_ga4->addHistory($gaStaff2->id, 'material_checked', "Material tersedia. Leadtime dimulai. Deadline: {$wo_ga4->deadline->format('d M Y')}.");

        // GA-WO5: Material tidak tersedia — staff memesan PR sendiri, leadtime belum mulai
        $wo_ga5 = $this->makeGaWO($userQC, 'Penggantian Karpet Ruang Direksi', 'Karpet ruang direksi sudah usang dan robek.', 'low', $catFasilitas);
        $wo_ga5->update([
            'status' => 'pending_parts', 'accepted_by' => $gaSectionHead->id,
            'accepted_at' => now()->subDays(3), 'assigned_member_id' => $gaStaff1->id,
            'deadline' => null, 'current_step_order' => 3,
        ]);
        WoPartOrder::create([
            'wo_id' => $wo_ga5->id, 'requested_by' => $gaStaff1->id,
            'request_note' => 'Karpet ukuran 4x6m warna abu-abu, belum ada stok.',
            'status' => 'pending_warehouse',
        ]);
        $wo_ga5->addHistory($userQC->id, 'created', 'WO dibuat.');
        $wo_ga5->addHistory($gaSectionHead->id, 'accepted', 'WO diterima.');
        $wo_ga5->addHistory($gaSectionHead->id, 'assigned_member', "Diassign ke {$gaStaff1->name}. Leadtime akan mulai setelah pengecekan material.");
        $wo_ga5->addHistory($gaStaff1->id, 'pending_parts', 'Material tidak tersedia. Dikembalikan ke Section Head untuk pemesanan barang.');

        // GA-WO6: Material diterima — Section Head menjadwalkan ulang
        $wo_ga6 = $this->makeGaWO($userIT, 'Pengadaan Kursi Kantor Ergonomis', 'Kursi kantor lama sudah rusak, perlu 5 unit baru.', 'medium', $catUmum);
        $accepted6 = now()->subDays(10);
        $received6 = now()->subHours(6);
        $wo_ga6->update([
            'status' => 'parts_received', 'accepted_by' => $gaSectionHead->id,
            'accepted_at' => $accepted6, 'assigned_member_id' => $gaStaff2->id,
            'deadline' => null, 'scheduled_start_at' => null, 'current_step_order' => 2,
        ]);
        WoPartOrder::create([
            'wo_id' => $wo_ga6->id, 'requested_by' => $gaStaff2->id, 'handled_by' => $gaSectionHead->id,
            'pr_number' => 'PR-GA-2026-0001', 'status' => 'received',
            'request_note' => 'Kursi ergonomis 5 unit, belum ada stok.',
            'warehouse_note' => 'Barang sudah tiba dan diterima.',
            'pr_date' => now()->subDays(9)->toDateString(),
            'expected_arrival' => now()->subDay()->toDateString(),
            'received_at' => $received6,
        ]);
        $wo_ga6->addHistory($userIT->id, 'created', 'WO dibuat.');
        $wo_ga6->addHistory($gaSectionHead->id, 'accepted', 'WO diterima.');
        $wo_ga6->addHistory($gaSectionHead->id, 'assigned_member', "Diassign ke {$gaStaff2->name}. Leadtime akan mulai setelah pengecekan material.");
        $wo_ga6->addHistory($gaStaff2->id, 'pending_parts', 'Material tidak tersedia. Dikembalikan ke Section Head untuk pemesanan barang.');
        $wo_ga6->addHistory($gaSectionHead->id, 'parts_received', 'Material diterima. Section Head menjadwalkan ulang dan assign ke staff.');

        // GA-WO7: Selesai dikerjakan — menunggu review requester
        $wo_ga7 = $this->makeGaWO($userProduksi, 'Pembersihan Area Parkir', 'Area parkir karyawan perlu dibersihkan dan dicat ulang.', 'low', $catFasilitas);
        $accepted7 = now()->subDays(4);
        $wo_ga7->update([
            'status' => 'completed', 'accepted_by' => $gaSectionHead->id,
            'accepted_at' => $accepted7, 'assigned_member_id' => $gaStaff1->id,
            'deadline' => $accepted7->copy()->addDays(3), 'completed_at' => now()->subHours(3),
            'planned_start_at' => $accepted7, 'planned_end_at' => $accepted7->copy()->addDays(3),
            'actual_start_at' => $accepted7, 'actual_end_at' => now()->subHours(3),
            'current_step_order' => 5,
        ]);
        $wo_ga7->addHistory($userProduksi->id, 'created', 'WO dibuat.');
        $wo_ga7->addHistory($gaSectionHead->id, 'accepted', 'WO diterima.');
        $wo_ga7->addHistory($gaSectionHead->id, 'assigned_member', "Diassign ke {$gaStaff1->name}. Leadtime akan mulai setelah pengecekan material.");
        $wo_ga7->addHistory($gaStaff1->id, 'material_checked', 'Material tersedia. Leadtime dimulai.');
        $wo_ga7->addHistory($gaStaff1->id, 'completed', 'Pekerjaan selesai.');

        // Historical finished GA WOs
        $this->finishedGaWO($userQC,       'Pengadaan Dispenser Air Kantor',    'Dispenser lama rusak, perlu penggantian.',     'low',    $gaStaff2, 30, 3, 0, 100, $userQC,       $gaSectionHead);
        $this->finishedGaWO($userProduksi, 'Perbaikan Atap Bocor Gudang B',     'Atap gudang B bocor saat hujan deras.',        'high',   $gaStaff1, 45, 5, 1, 80,  $userProduksi, $gaSectionHead);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function loadDeptData(): void
    {
        $this->mtc = Department::where('slug', 'maintenance')->first();
        $this->qa  = Department::where('slug', 'qa')->first();
        $this->ga  = Department::where('slug', 'ga')->first();

        foreach (DepartmentRole::where('department_id', $this->mtc->id)->get() as $r) {
            $this->mtcRoles[$r->key] = $r->id;
        }
        foreach (DepartmentRole::where('department_id', $this->qa->id)->get() as $r) {
            $this->qaRoles[$r->key] = $r->id;
        }
        foreach (DepartmentRole::where('department_id', $this->ga->id)->get() as $r) {
            $this->gaRoles[$r->key] = $r->id;
        }
        foreach (\App\Models\WoCategory::where('department_id', $this->mtc->id)->get() as $c) {
            $this->mtcCats[$c->name] = $c;
        }
        foreach (\App\Models\WoCategory::where('department_id', $this->qa->id)->get() as $c) {
            $this->qaCats[$c->name] = $c;
        }
        foreach (\App\Models\WoCategory::where('department_id', $this->ga->id)->get() as $c) {
            $this->gaCats[$c->name] = $c;
        }
    }

    private function mkMember(string $name, string $email, MaintenanceUnit $unit, MaintenanceGroup $group): User
    {
        return User::create([
            'name' => $name, 'email' => $email,
            'password' => Hash::make('password'), 'role' => 'member',
            'department' => 'Maintenance', 'unit_id' => $unit->id, 'group_id' => $group->id,
            'department_id' => $this->mtc->id, 'dept_role_id' => $this->mtcRoles['member'],
        ]);
    }

    private function mkQaMember(string $name, string $email): User
    {
        return User::create([
            'name' => $name, 'email' => $email,
            'password' => Hash::make('password'), 'role' => 'qa_member', 'department' => 'QA',
            'department_id' => $this->qa->id, 'dept_role_id' => $this->qaRoles['member'],
        ]);
    }

    private function mkGaStaff(string $name, string $email): User
    {
        return User::create([
            'name' => $name, 'email' => $email,
            'password' => Hash::make('password'), 'role' => 'member', 'department' => 'GA',
            'department_id' => $this->ga->id, 'dept_role_id' => $this->gaRoles['staff'],
        ]);
    }

    private function makeWO(User $requester, string $title, string $desc, string $category, string $priority, WoCategory $cat): WorkOrder
    {
        return WorkOrder::create([
            'wo_number'            => WorkOrder::generateWoNumber($this->mtc),
            'title'                => $title, 'description' => $desc,
            'category'             => $category, 'priority' => $priority,
            'requester_id'         => $requester->id,
            'destination'          => 'maintenance',
            'target_department_id' => $this->mtc->id,
            'wo_category_id'       => $cat->id,
            'leadtime_days'        => $cat->leadtime_days,
            'status'               => 'pending',
            'current_step_order'   => 1,
        ]);
    }

    private function makeQaWO(User $requester, string $title, string $desc, string $priority, WoCategory $cat): WorkOrder
    {
        return WorkOrder::create([
            'wo_number'            => WorkOrder::generateWoNumber($this->qa),
            'requester_id'         => $requester->id,
            'destination'          => 'qa',
            'target_department_id' => $this->qa->id,
            'wo_category_id'       => $cat->id,
            'leadtime_days'        => $cat->leadtime_days,
            'status'               => 'pending',
            'current_step_order'   => 1,
        ]);
    }

    private function makeGaWO(User $requester, string $title, string $desc, string $priority, WoCategory $cat): WorkOrder
    {
        return WorkOrder::create([
            'wo_number'            => WorkOrder::generateWoNumber($this->ga),
            'requester_id'         => $requester->id,
            'destination'          => 'ga',
            'target_department_id' => $this->ga->id,
            'wo_category_id'       => $cat->id,
            'leadtime_days'        => $cat->leadtime_days,
            'status'               => 'pending',
            'current_step_order'   => 1,
        ]);
    }

    private function finishedGaWO(
        User $requester, string $title, string $desc, string $priority, User $staff,
        int $daysAgo, int $completedDay, int $reworkCount, int $score, User $reviewer,
        ?User $sectionHead = null
    ): void {
        $wo = $this->makeGaWO($requester, $title, $desc, $priority, $this->gaCats['Umum']);
        $accepted = now()->subDays($daysAgo);
        $wo->update([
            'status' => 'finished', 'accepted_by' => $sectionHead?->id,
            'accepted_at' => $accepted, 'assigned_member_id' => $staff->id,
            'deadline' => $accepted->copy()->addDays(3),
            'planned_start_at' => $accepted, 'planned_end_at' => $accepted->copy()->addDays(3),
            'actual_start_at' => $accepted,
            'completed_at' => $accepted->copy()->addDays($completedDay),
            'actual_end_at' => $accepted->copy()->addDays($completedDay),
            'finished_at' => $accepted->copy()->addDays($completedDay + 1),
            'rework_count' => $reworkCount, 'score' => $score,
            'current_step_order' => null,
        ]);
        $wo->addHistory($requester->id, 'created', 'WO dibuat.');
        $wo->addHistory($staff->id, 'assigned_member', "Diassign ke {$staff->name}.");
        $wo->addHistory($staff->id, 'material_checked', 'Material tersedia. Leadtime dimulai.');
        $wo->addHistory($staff->id, 'completed', 'Pekerjaan selesai.');
        if ($reworkCount > 0) {
            $wo->addHistory($reviewer->id, 'rework', 'Rework diminta.');
            $wo->addHistory($staff->id, 'completed', 'Rework selesai.');
        }
        $wo->addHistory($reviewer->id, 'finished', "Pekerjaan disetujui. Skor: {$score}.");
    }

    private function finishedWO(
        User $requester, string $title, string $desc, string $category, string $priority,
        MaintenanceUnit $unit, MaintenanceGroup $group,
        User $uh, User $gh, User $member,
        int $daysAgo, int $completedDay, int $reworkCount, int $score,
        User $reviewer, WoCategory $cat
    ): void {
        $wo = $this->makeWO($requester, $title, $desc, $category, $priority, $cat);
        $accepted = now()->subDays($daysAgo);
        $wo->update([
            'status' => 'finished', 'unit_id' => $unit->id,
            'assigned_group_id' => $group->id, 'assigned_member_id' => $member->id,
            'accepted_by' => $uh->id, 'accepted_at' => $accepted,
            'deadline' => $accepted->copy()->addDays(7),
            'planned_start_at' => $accepted, 'planned_end_at' => $accepted->copy()->addDays(7),
            'actual_start_at' => $accepted,
            'completed_at' => $accepted->copy()->addDays($completedDay),
            'actual_end_at' => $accepted->copy()->addDays($completedDay),
            'finished_at' => $accepted->copy()->addDays($completedDay + 1),
            'rework_count' => $reworkCount, 'score' => $score,
            'current_step_order' => null,
        ]);
        $wo->addHistory($requester->id, 'created', 'WO dibuat.');
        $wo->addHistory($uh->id, 'accepted', 'WO diterima.');
        $wo->addHistory($gh->id, 'assigned_member', "Diassign ke {$member->name}.");
        $wo->addHistory($member->id, 'completed', 'Pekerjaan selesai.');
        if ($reworkCount > 0) {
            $wo->addHistory($reviewer->id, 'rework', 'Rework diminta.');
            $wo->addHistory($member->id, 'completed', 'Rework selesai.');
        }
        $wo->addHistory($reviewer->id, 'finished', "Pekerjaan disetujui. Skor: {$score}.");
    }

    private function finishedQaWO(
        User $requester, string $title, string $desc, string $priority,
        User $qaGH, User $member,
        int $daysAgo, int $completedDay, int $reworkCount, int $score,
        User $reviewer, WoCategory $cat
    ): void {
        $wo = $this->makeQaWO($requester, $title, $desc, $priority, $cat);
        $accepted = now()->subDays($daysAgo);
        $wo->update([
            'status' => 'finished', 'accepted_by' => $qaGH->id, 'accepted_at' => $accepted,
            'assigned_member_id' => $member->id,
            'deadline' => $accepted->copy()->addDays(3),
            'planned_start_at' => $accepted, 'planned_end_at' => $accepted->copy()->addDays(3),
            'actual_start_at' => $accepted,
            'completed_at' => $accepted->copy()->addDays($completedDay),
            'actual_end_at' => $accepted->copy()->addDays($completedDay),
            'finished_at' => $accepted->copy()->addDays($completedDay + 1),
            'rework_count' => $reworkCount, 'score' => $score,
            'current_step_order' => null,
        ]);
        $wo->addHistory($requester->id, 'created', 'WO dibuat.');
        $wo->addHistory($qaGH->id, 'accepted', 'WO diterima.');
        $wo->addHistory($qaGH->id, 'assigned_member', "Diassign ke {$member->name}.");
        $wo->addHistory($member->id, 'completed', 'Pekerjaan QA selesai.');
        if ($reworkCount > 0) {
            $wo->addHistory($reviewer->id, 'rework', 'Rework diminta.');
            $wo->addHistory($member->id, 'completed', 'Rework selesai.');
        }
        $wo->addHistory($reviewer->id, 'finished', "Pekerjaan disetujui. Skor: {$score}.");
    }

    private function historyProcurement(
        User $requester, User $uh, User $warehouse, string $prNum,
        int $prDaysAgo, int $procDays, string $title,
        string $category, string $priority, MaintenanceUnit $unit, WoCategory $cat
    ): void {
        $wo = $this->makeWO($requester, $title, 'Kebutuhan penggantian komponen.', $category, $priority, $cat);
        $prDate     = now()->subDays($prDaysAgo);
        $receivedAt = $prDate->copy()->addDays($procDays);
        $wo->update([
            'status' => 'parts_received', 'unit_id' => $unit->id,
            'accepted_by' => $uh->id, 'accepted_at' => $prDate->copy()->subDays(3),
            'current_step_order' => 3,
        ]);
        WoPartOrder::create([
            'wo_id' => $wo->id, 'requested_by' => $uh->id, 'handled_by' => $warehouse->id,
            'pr_number' => $prNum, 'status' => 'received',
            'pr_date' => $prDate->toDateString(),
            'expected_arrival' => $prDate->copy()->addDays(30)->toDateString(),
            'received_at' => $receivedAt,
        ]);
    }
}
