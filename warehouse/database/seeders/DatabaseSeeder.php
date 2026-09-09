<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\MinimumStock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'IT',    'name' => 'Information Technology'],
            ['code' => 'PROD',  'name' => 'Produksi'],
            ['code' => 'WH',    'name' => 'Warehouse'],
            ['code' => 'PURCH', 'name' => 'Purchasing'],
            ['code' => 'MTC',   'name' => 'Maintenance'],
            ['code' => 'GA',    'name' => 'General Affair'],
        ];
        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }

        $itDept   = Department::where('code', 'IT')->first();
        $whDept   = Department::where('code', 'WH')->first();
        $prodDept = Department::where('code', 'PROD')->first();

        User::firstOrCreate(['email' => 'superadmin@warehouse.com'], [
            'npk'           => 'SA001',
            'name'          => 'Super Administrator',
            'password'      => Hash::make('password'),
            'role'          => 'superadmin',
            'department_id' => $itDept->id,
            'qr_code'       => Str::uuid(),
            'is_active'     => true,
        ]);

        User::firstOrCreate(['email' => 'admin@warehouse.com'], [
            'npk'           => 'ADM001',
            'name'          => 'Admin Warehouse',
            'password'      => Hash::make('password'),
            'role'          => 'admin',
            'department_id' => $whDept->id,
            'qr_code'       => Str::uuid(),
            'is_active'     => true,
        ]);

        User::firstOrCreate(['email' => 'user@warehouse.com'], [
            'npk'           => 'USR001',
            'name'          => 'User Produksi',
            'password'      => Hash::make('password'),
            'role'          => 'user',
            'department_id' => $prodDept->id,
            'qr_code'       => Str::uuid(),
            'is_active'     => true,
        ]);

        User::firstOrCreate(['email' => 'section@warehouse.com'], [
            'npk'           => 'SEC001',
            'name'          => 'Section Head PPIC',
            'password'      => Hash::make('password'),
            'role'          => 'section',
            'department_id' => Department::where('code', 'PROD')->first()->id,
            'qr_code'       => Str::uuid(),
            'is_active'     => true,
        ]);

        User::firstOrCreate(['email' => 'manager@warehouse.com'], [
            'npk'           => 'MGR001',
            'name'          => 'Manager Operasional',
            'password'      => Hash::make('password'),
            'role'          => 'manager',
            'department_id' => Department::where('code', 'WH')->first()->id,
            'qr_code'       => Str::uuid(),
            'is_active'     => true,
        ]);

        User::firstOrCreate(['email' => 'purchasing@warehouse.com'], [
            'npk'           => 'PUR001',
            'name'          => 'Staff Purchasing',
            'password'      => Hash::make('password'),
            'role'          => 'purchasing',
            'department_id' => Department::where('code', 'PURCH')->first()->id,
            'qr_code'       => Str::uuid(),
            'is_active'     => true,
        ]);

        User::firstOrCreate(['email' => 'director@warehouse.com'], [
            'npk'           => 'DIR001',
            'name'          => 'Direktur',
            'password'      => Hash::make('password'),
            'role'          => 'director',
            'department_id' => Department::where('code', 'IT')->first()->id,
            'qr_code'       => Str::uuid(),
            'is_active'     => true,
        ]);

        User::firstOrCreate(['email' => 'ga@warehouse.com'], [
            'npk'           => 'GA001',
            'name'          => 'Staff General Affair',
            'password'      => Hash::make('password'),
            'role'          => 'ga',
            'department_id' => Department::where('code', 'GA')->first()->id,
            'qr_code'       => Str::uuid(),
            'is_active'     => true,
        ]);

        // UOM seeder
        $uoms = [
            ['code' => 'PCS',  'name' => 'Pieces'],
            ['code' => 'BOX',  'name' => 'Box'],
            ['code' => 'RIM',  'name' => 'Rim'],
            ['code' => 'BTL',  'name' => 'Botol'],
            ['code' => 'LBR',  'name' => 'Lembar'],
            ['code' => 'BH',   'name' => 'Buah'],
            ['code' => 'DUS',  'name' => 'Dus'],
            ['code' => 'SET',  'name' => 'Set'],
            ['code' => 'PAK',  'name' => 'Pak'],
            ['code' => 'ROLL', 'name' => 'Roll'],
        ];
        foreach ($uoms as $uom) {
            \App\Models\Uom::firstOrCreate(['code' => $uom['code']], $uom);
        }

        // Sample ATK items
        $pcs  = \App\Models\Uom::where('code','PCS')->first();
        $box  = \App\Models\Uom::where('code','BOX')->first();
        $rim  = \App\Models\Uom::where('code','RIM')->first();
        $btl  = \App\Models\Uom::where('code','BTL')->first();
        $atkItems = [
            ['item_code'=>'ATK-001','name'=>'Pulpen Ballpoint','uom_id'=>$pcs->id,'category'=>'Alat Tulis'],
            ['item_code'=>'ATK-002','name'=>'Kertas HVS A4 70gr','uom_id'=>$rim->id,'category'=>'Kertas'],
            ['item_code'=>'ATK-003','name'=>'Staples No.10','uom_id'=>$box->id,'category'=>'Alat Tulis'],
            ['item_code'=>'ATK-004','name'=>'Tinta Printer Hitam','uom_id'=>$btl->id,'category'=>'Tinta'],
            ['item_code'=>'ATK-005','name'=>'Spidol Whiteboard','uom_id'=>$pcs->id,'category'=>'Alat Tulis'],
            ['item_code'=>'ATK-006','name'=>'Map Plastik Transparan','uom_id'=>$pcs->id,'category'=>'File'],
            ['item_code'=>'ATK-007','name'=>'Buku Tulis A5','uom_id'=>$pcs->id,'category'=>'Buku'],
            ['item_code'=>'ATK-008','name'=>'Isolasi Bening','uom_id'=>$pcs->id,'category'=>'Perlengkapan'],
        ];
        foreach ($atkItems as $atk) {
            \App\Models\ProcurementItem::firstOrCreate(['item_code'=>$atk['item_code']], $atk);
        }

        $items = [
            ['item_code' => 'ITM-001', 'description' => 'Baut M8x30mm',          'unit' => 'PCS', 'category' => 'Fastener',   'is_memo' => false],
            ['item_code' => 'ITM-002', 'description' => 'Mur M8',                 'unit' => 'PCS', 'category' => 'Fastener',   'is_memo' => false],
            ['item_code' => 'ITM-003', 'description' => 'Bearing 6205',           'unit' => 'PCS', 'category' => 'Bearing',    'is_memo' => false],
            ['item_code' => 'ITM-004', 'description' => 'Oli Mesin SAE 40',       'unit' => 'LTR', 'category' => 'Lubricant',  'is_memo' => false],
            ['item_code' => 'ITM-005', 'description' => 'Sarung Tangan Safety',   'unit' => 'PSG', 'category' => 'Safety',     'is_memo' => true],
            ['item_code' => 'ITM-006', 'description' => 'Helm Safety',            'unit' => 'PCS', 'category' => 'Safety',     'is_memo' => true],
            ['item_code' => 'ITM-007', 'description' => 'Kabel NYY 2x1.5mm',      'unit' => 'MTR', 'category' => 'Electrical', 'is_memo' => false],
            ['item_code' => 'ITM-008', 'description' => 'Grease Shell Gadus S2',  'unit' => 'KG',  'category' => 'Lubricant',  'is_memo' => false],
        ];

        foreach ($items as $itemData) {
            $item = Item::firstOrCreate(
                ['item_code' => $itemData['item_code']],
                array_merge($itemData, ['warehouse' => 'WH01'])
            );

            if (!$item->is_memo) {
                ItemStock::firstOrCreate(
                    ['item_id' => $item->id, 'warehouse' => 'WH01'],
                    ['qty_on_hand' => rand(10, 100), 'qty_reserved' => 0, 'last_sync_at' => now()]
                );
            }

            MinimumStock::firstOrCreate(
                ['item_id' => $item->id, 'warehouse' => 'WH01'],
                ['min_qty' => rand(5, 20), 'is_active' => true]
            );
        }
    }
}
