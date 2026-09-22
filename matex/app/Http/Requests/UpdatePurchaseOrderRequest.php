<?php

namespace App\Http\Requests;

use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('purchase_order')) ?? false;
    }

    public function rules(): array
    {
        $po = $this->route('purchase_order');

        return [
            'po_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('purchase_orders', 'po_number')->ignore($po->id),
            ],
            'supplier_code' => ['required', 'string', 'exists:qad_suppliers,qad_code'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_number' => ['required', 'string', 'exists:qad_items,qad_code', 'distinct'],
            'items.*.qty_ordered' => ['required', 'integer', 'gt:0'],
            'items.*.schedules' => ['required', 'array', 'min:1'],
            'items.*.schedules.*.scheduled_date' => ['required', 'date'],
            'items.*.schedules.*.qty' => ['required', 'integer', 'gt:0'],
            'items.*.schedules.*.ohp_supplier_code' => [
                'required',
                'string',
                Rule::exists('qad_suppliers', 'qad_code')->where('category', CompanyType::Ohp->value),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateOhpNotSameAsSupplierRm($validator);
            $this->validateSupplierHasAccount($validator);

            foreach ($this->input('items', []) as $index => $item) {
                $ordered = (int) round((float) ($item['qty_ordered'] ?? 0));
                $scheduled = (int) round(
                    collect($item['schedules'] ?? [])->sum(fn ($s) => (float) ($s['qty'] ?? 0))
                );

                if ($scheduled !== $ordered) {
                    $remaining = $ordered - $scheduled;
                    $validator->errors()->add(
                        "items.$index.schedules",
                        $remaining > 0
                            ? "Total qty jadwal harus sama dengan qty order. Masih ada sisa {$remaining} kg."
                            : 'Total qty jadwal melebihi qty order.'
                    );
                }
            }
        });
    }

    /**
     * Both supplier_code and ohp_supplier_code are QAD vendor codes now, so
     * this is a direct string comparison — no more resolving to companies.id
     * first.
     */
    private function validateOhpNotSameAsSupplierRm(Validator $validator): void
    {
        $supplierCode = $this->input('supplier_code');

        if (blank($supplierCode)) {
            return;
        }

        foreach ($this->input('items', []) as $itemIndex => $item) {
            foreach (($item['schedules'] ?? []) as $scheduleIndex => $schedule) {
                if (($schedule['ohp_supplier_code'] ?? null) === $supplierCode) {
                    $validator->errors()->add(
                        "items.$itemIndex.schedules.$scheduleIndex.ohp_supplier_code",
                        'Tujuan OHP tidak boleh sama dengan Supplier RM.'
                    );
                }
            }
        }
    }

    /**
     * Re-enforces the picker's "only suppliers with a portal account"
     * filter server-side — a supplier with no login can't act on the PO
     * (confirm qty, receive DN, etc), so this must hold even if a request
     * is crafted directly.
     */
    private function validateSupplierHasAccount(Validator $validator): void
    {
        $supplierCode = $this->input('supplier_code');

        if (filled($supplierCode) && ! $this->companyHasAccount($supplierCode)) {
            $validator->errors()->add('supplier_code', 'Supplier ini belum punya akun portal.');
        }

        foreach ($this->input('items', []) as $itemIndex => $item) {
            foreach (($item['schedules'] ?? []) as $scheduleIndex => $schedule) {
                $code = $schedule['ohp_supplier_code'] ?? null;

                if (filled($code) && ! $this->companyHasAccount($code)) {
                    $validator->errors()->add(
                        "items.$itemIndex.schedules.$scheduleIndex.ohp_supplier_code",
                        'Supplier OHP ini belum punya akun portal.'
                    );
                }
            }
        }
    }

    private function companyHasAccount(string $qadCode): bool
    {
        return Company::where('code', $qadCode)->whereHas('users')->exists();
    }
}
