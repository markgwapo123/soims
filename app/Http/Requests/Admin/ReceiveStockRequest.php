<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceiveStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_inventory') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'stock_no' => ['required_without:item_id', 'nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('items', 'stock_no')->ignore($this->input('item_id'))],
            'name' => ['required_without:item_id', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'quality' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required_without:item_id', 'nullable', 'integer', 'exists:categories,id'],
            'classification' => ['required_without:item_id', 'nullable', 'in:consumable,accountable'],
            'unit_of_measure' => ['required_without:item_id', 'nullable', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'lot_no' => ['nullable', 'string', 'max:255'],
            'received_date' => ['required', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:received_date'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
