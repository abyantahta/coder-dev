<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmOhpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('confirmAsOhp', $this->route('delivery_note')) ?? false;
    }

    public function rules(): array
    {
        return [
            'sj_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
