<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDnDeliveryDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('delivery_note');

        return $this->user()?->can('updateDeliveryDate', $note) ?? false;
    }

    public function rules(): array
    {
        return [
            'delivery_date' => ['required', 'date'],
        ];
    }
}
