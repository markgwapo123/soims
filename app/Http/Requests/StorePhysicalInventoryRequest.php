<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePhysicalInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_inventory') ?? false;
    }

    public function rules(): array
    {
        return [
            'inventory_no' => ['required', 'string', 'max:255', 'unique:physical_inventories,inventory_no'],
            'conducted_at' => ['required', 'date'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'inventory_ids' => ['nullable', 'array'],
            'inventory_ids.*' => ['required', 'integer', 'distinct', 'exists:inventories,id'],
            'accountable_item_ids' => ['nullable', 'array'],
            'accountable_item_ids.*' => ['required', 'integer', 'distinct', 'exists:accountable_items,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $inventoryIds = $this->input('inventory_ids', []);
            $accountableItemIds = $this->input('accountable_item_ids', []);
            $inventoryCount = is_array($inventoryIds) ? count($inventoryIds) : 0;
            $assetCount = is_array($accountableItemIds) ? count($accountableItemIds) : 0;
            if ($inventoryCount + $assetCount < 1) {
                $validator->errors()->add('inventory_ids', 'Select at least one stock balance or accountable asset to count.');
            }
        }];
    }
}