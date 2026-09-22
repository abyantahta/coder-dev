<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('receive', $this->route('delivery_note')) ?? false;
    }

    public function rules(): array
    {
        $dn = $this->route('delivery_note');
        $remaining = $dn ? $dn->remaining_qty : null;

        return [
            'received_qty' => [
                'nullable',
                'integer',
                'gt:0',
                $remaining !== null ? "max:{$remaining}" : 'max:999999999',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
