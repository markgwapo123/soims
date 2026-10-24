<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_catalog') ?? false;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');
        return [
            'stock_no' => array_filter(['required', 'string', 'max:100', 'alpha_dash', $creating ? null : Rule::unique('items', 'stock_no')->ignore($this->route('item')?->id)]),
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'quality' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'classification' => ['required', Rule::in(['consumable', 'accountable'])],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'default_unit_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'quantity' => $creating ? ['required', 'numeric', 'min:0', 'decimal:0,3'] : ['prohibited'],
            'confirm_existing' => $creating ? ['sometimes', 'boolean'] : ['prohibited'],
        ];
    }
}
