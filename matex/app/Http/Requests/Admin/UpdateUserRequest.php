<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    /**
     * Purchasing can manage PPIC/Purchasing/Supplier RM/Supplier OHP
     * accounts but not Admin — an existing Admin account can only be
     * touched by another Admin, even to just edit its name.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user?->hasRole('admin', 'purchasing')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return ! $this->route('user')->hasRole('admin');
    }

    public function rules(): array
    {
        $user = $this->route('user');

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
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
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
