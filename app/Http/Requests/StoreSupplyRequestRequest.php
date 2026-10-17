<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('submit_supply_requests') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Rows left blank on the form are not part of the request.
        $items = collect($this->input('items', []))
            ->filter(fn ($row) => is_array($row) && isset($row['quantity']) && (float) $row['quantity'] > 0)
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'purpose' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'distinct', Rule::exists('items', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999999'],
        ];
    }

    public function messages(): array
    {
        return ['items.required' => 'Enter a quantity for at least one item.'];
    }
}
