<?php

namespace App\Actions\Concerns;

use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\QadSupplier;

trait ResolvesQadSupplier
{
    /**
     * Find or create the local Company record for a QAD supplier/vendor
     * code. `code` is the shared key between `companies` and
     * `qad_suppliers` — same pattern as ResolvesQadItem. An existing
     * company's manually-edited fields are left alone; name/address/type
     * are only seeded from QAD the first time a code is used.
     *
     * Type comes from the supplier's manually-tagged category (raw_mat/ohp)
     * — falls back to RawMat only if it's somehow still uncategorized (the
     * PO Supplier RM picker fails open and shows uncategorized suppliers
     * too, so this can be reached without a category set).
     */
    private function resolveSupplierFromQadCode(string $qadCode): Company
    {
        $existing = Company::where('code', $qadCode)->first();

        if ($existing) {
            return $existing;
        }

        $qadSupplier = QadSupplier::where('qad_code', $qadCode)->first();

        return Company::create([
            'code' => $qadCode,
            'name' => $qadSupplier?->name ?: $qadCode,
            'type' => $qadSupplier?->category ?: CompanyType::RawMat,
            'address' => $qadSupplier?->address_line1,
            'is_active' => true,
        ]);
    }
}
