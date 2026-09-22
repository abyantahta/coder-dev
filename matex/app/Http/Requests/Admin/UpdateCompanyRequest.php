<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'purchasing') ?? false;
    }

    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('companies', 'code')->ignore($company->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(CompanyType::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
