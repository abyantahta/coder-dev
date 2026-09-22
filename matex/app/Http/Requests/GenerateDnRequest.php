<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateDnRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schedule = $this->route('delivery_schedule');

        return $this->user()?->can('generateDn', $schedule) ?? false;
    }

    public function rules(): array
    {
        return [
            'rm_sj_number' => ['required', 'string', 'max:100'],
            'delivery_date' => ['nullable', 'date'],
        ];
    }
}
