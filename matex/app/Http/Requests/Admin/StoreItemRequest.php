<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'purchasing') ?? false;
    }

    public function rules(): array
    {
        return [
            'item_number' => ['required', 'string', 'max:50', 'unique:items,item_number'],
            'description' => ['required', 'string', 'max:255'],
            'uom' => ['required', 'string', 'max:20'],
            'subcont_ohp_id' => [
                'required',
                Rule::exists('companies', 'id')->where('type', CompanyType::Ohp->value),
            ],
            'price' => ['required', 'numeric', 'gte:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
