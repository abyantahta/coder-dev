<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmByRmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('confirmAsRm', $this->route('purchase_order')) ?? false;
    }

    public function rules(): array
    {
        // Supplier RM can move a schedule to a different day, but only
        // within the PO's due-date month — same boundary the confirmation
        // matrix itself is built around (one column per day of that month).
        $dueDate = $this->route('purchase_order')?->due_date;

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.qty_confirmed' => ['required', 'integer', 'gt:0'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.id' => ['required', 'exists:delivery_schedules,id'],
            'schedules.*.qty_confirmed' => ['required', 'integer', 'gt:0'],
            'schedules.*.scheduled_date' => array_filter([
                'nullable',
                'date',
                $dueDate ? 'after_or_equal:'.$dueDate->copy()->startOfMonth()->toDateString() : null,
                $dueDate ? 'before_or_equal:'.$dueDate->copy()->endOfMonth()->toDateString() : null,
            ]),
        ];
    }
}
