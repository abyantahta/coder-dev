<?php

namespace App\Http\Requests;

use App\Enums\CompanyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Forecast::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_rm_id' => [
                'required',
                Rule::exists('companies', 'id')->where('type', CompanyType::RawMat->value),
            ],
            'period_month' => ['required', 'date_format:Y-m'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,xlsx,xls,csv,jpg,jpeg,png', 'max:10240'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['required', 'exists:items,id', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'gt:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasItems = collect($this->input('items', []))
                ->contains(fn ($item) => filled($item['item_id'] ?? null) && (int) ($item['qty'] ?? 0) > 0);
            $hasFile = $this->hasFile('file');
            $existing = $this->route('forecast');

            if (! $hasItems && ! $hasFile && ! $existing?->file_path) {
                $validator->errors()->add(
                    'items',
                    'Lengkapi minimal satu item forecast atau unggah file forecast.'
                );
            }
        });
    }
}
