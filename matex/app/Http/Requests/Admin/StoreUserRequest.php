<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    /**
     * Purchasing can create PPIC/Purchasing/Supplier RM/Supplier OHP
     * accounts but not Admin — only an Admin can create another Admin.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'purchasing') ?? false;
    }

    public function rules(): array
    {
        $allowedRoles = $this->user()->hasRole('admin')
            ? collect(UserRole::cases())->pluck('value')->all()
            : [
                UserRole::Ppic->value,
                UserRole::Purchasing->value,
                UserRole::SupplierRm->value,
                UserRole::SupplierOhp->value,
            ];

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($allowedRoles)],
            'supplier_code' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $role = $this->input('role');

            if (! in_array($role, [UserRole::SupplierRm->value, UserRole::SupplierOhp->value], true)) {
                return;
            }

            $expectedCategory = $role === UserRole::SupplierRm->value
                ? CompanyType::RawMat->value
                : CompanyType::Ohp->value;

            $code = $this->input('supplier_code');

            if (blank($code)) {
                $validator->errors()->add('supplier_code', 'Pilih supplier dari master data QAD.');

                return;
            }

            $exists = \App\Models\QadSupplier::where('qad_code', $code)
                ->where('category', $expectedCategory)
                ->exists();

            if (! $exists) {
                $validator->errors()->add(
                    'supplier_code',
                    'Kode supplier tidak ditemukan atau kategorinya tidak sesuai role yang dipilih.'
                );
            }
        });
    }
}
