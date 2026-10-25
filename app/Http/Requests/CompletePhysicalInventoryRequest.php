<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CompletePhysicalInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_inventory') ?? false;
    }

    public function rules(): array
    {
        return [
            'counts' => ['required', 'array', 'min:1'],
            'counts.*' => ['required', 'array'],
            'counts.*.quantity_per_physical_count' => ['required', 'integer', 'min:0', 'max:999999999'],
            'counts.*.new_property_no' => ['nullable', 'string', 'max:255'],
            'counts.*.remarks' => ['nullable', 'string', 'max:2000'],
            'found_items' => ['nullable', 'array'],
            'found_items.*.article' => ['nullable', 'string', 'max:255'],
            'found_items.*.description' => ['nullable', 'string', 'max:5000'],
            'found_items.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            'found_items.*.unit_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'found_items.*.quantity_per_physical_count' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'found_items.*.new_property_no' => ['nullable', 'string', 'max:255'],
            'found_items.*.remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $foundItems = $this->input('found_items', []);
            foreach (is_array($foundItems) ? $foundItems : [] as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $hasData = collect($row)->contains(fn ($value) => $value !== null && trim((string) $value) !== '');
                if (! $hasData) {
                    continue;
                }
                foreach (['article', 'description', 'quantity_per_physical_count'] as $field) {
                    if (! isset($row[$field]) || trim((string) $row[$field]) === '') {
                        $validator->errors()->add("found_items.{$index}.{$field}", 'Complete the article, description, and physical quantity for each found item.');
                    }
                }
            }
        }];
    }
}