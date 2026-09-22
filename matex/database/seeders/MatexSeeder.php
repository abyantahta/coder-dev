<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MatexSeeder extends Seeder
{
    public function run(): void
    {
        $sdi = Company::create([
            'code' => 'SDI',
            'name' => 'PT. SDI',
            'type' => CompanyType::Sdi,
            'address' => 'Jakarta, Indonesia',
        ]);

        $rmSuppliers = [
            ['code' => 'RM01', 'name' => 'PT. Raw Material Prima', 'address' => 'Bekasi, Indonesia'],
            ['code' => 'RM02', 'name' => 'PT. Bahan Baku Mandiri', 'address' => 'Tangerang, Indonesia'],
            ['code' => 'RM03', 'name' => 'PT. Metal Indo Jaya', 'address' => 'Cikarang, Indonesia'],
        ];

        $rmCompanies = [];
        foreach ($rmSuppliers as $data) {
            $rmCompanies[] = Company::create([
                ...$data,
                'type' => CompanyType::RawMat,
            ]);
        }

        $ohpSuppliers = [
            ['code' => 'OHP01', 'name' => 'PT. OH Part Nusantara', 'address' => 'Karawang, Indonesia'],
            ['code' => 'OHP02', 'name' => 'PT. Outsource Hand Parts', 'address' => 'Purwakarta, Indonesia'],
            ['code' => 'OHP03', 'name' => 'PT. Partindo Sejahtera', 'address' => 'Subang, Indonesia'],
        ];

        $ohpCompanies = [];
        foreach ($ohpSuppliers as $data) {
            $ohpCompanies[] = Company::create([
                ...$data,
                'type' => CompanyType::Ohp,
            ]);
        }

        $users = [
            ['name' => 'Admin Matex', 'email' => 'admin@matex.test', 'role' => UserRole::Admin, 'company_id' => $sdi->id],
            ['name' => 'Dita Purchasing', 'email' => 'dita@matex.test', 'role' => UserRole::Purchasing, 'company_id' => $sdi->id],
            ['name' => 'PPIC SDI', 'email' => 'ppic@matex.test', 'role' => UserRole::Ppic, 'company_id' => $sdi->id],
            ['name' => 'Supplier RM Prima', 'email' => 'rm01@matex.test', 'role' => UserRole::SupplierRm, 'company_id' => $rmCompanies[0]->id],
            ['name' => 'Supplier RM Mandiri', 'email' => 'rm02@matex.test', 'role' => UserRole::SupplierRm, 'company_id' => $rmCompanies[1]->id],
            ['name' => 'Supplier RM Metal', 'email' => 'rm03@matex.test', 'role' => UserRole::SupplierRm, 'company_id' => $rmCompanies[2]->id],
            ['name' => 'Supplier OHP Nusantara', 'email' => 'ohp01@matex.test', 'role' => UserRole::SupplierOhp, 'company_id' => $ohpCompanies[0]->id],
            ['name' => 'Supplier OHP Hand Parts', 'email' => 'ohp02@matex.test', 'role' => UserRole::SupplierOhp, 'company_id' => $ohpCompanies[1]->id],
            ['name' => 'Supplier OHP Partindo', 'email' => 'ohp03@matex.test', 'role' => UserRole::SupplierOhp, 'company_id' => $ohpCompanies[2]->id],
        ];

        foreach ($users as $data) {
            User::create([
                ...$data,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        $items = [
            [
                'item_number' => 'RM-AL-001',
                'description' => 'Aluminium Ingot Grade A',
                'uom' => 'kg',
                'price' => 45000,
                'subcont_ohp_id' => $ohpCompanies[0]->id,
            ],
            [
                'item_number' => 'RM-CU-002',
                'description' => 'Copper Rod 8mm',
                'uom' => 'kg',
                'price' => 125000,
                'subcont_ohp_id' => $ohpCompanies[1]->id,
            ],
            [
                'item_number' => 'RM-ST-003',
                'description' => 'Steel Coil SPCC',
                'uom' => 'kg',
                'price' => 18000,
                'subcont_ohp_id' => $ohpCompanies[2]->id,
            ],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
