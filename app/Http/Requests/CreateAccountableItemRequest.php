<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAccountableItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_accountable_items') ?? false;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('classification', 'accountable')],
            'property_no' => ['required', 'string', 'max:255', 'unique:accountable_items,property_no'],
            'barcode' => ['required', 'string', 'max:255', 'unique:accountable_items,barcode'],
            'serial_no' => ['nullable', 'string', 'max:255', 'unique:accountable_items,serial_no'],
            'date_acquired' => ['nullable', 'date'],
            'unit_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:30'],
        ];
    }
}