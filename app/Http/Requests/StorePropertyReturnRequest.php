<?php

namespace App\Http\Requests;

use App\Models\PropertyReturn;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_accountable_items') ?? false;
    }

    public function rules(): array
    {
        return [
            'prs_no' => ['required', 'string', 'max:255', 'unique:property_returns,prs_no'],
            'document_no' => ['nullable', 'string', 'max:255'],
            'issue_no' => ['nullable', 'string', 'max:255'],
            'office_name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'in:'.implode(',', [
                PropertyReturn::PURPOSE_REPAIR,
                PropertyReturn::PURPOSE_DISPOSAL,
                PropertyReturn::PURPOSE_RETURNED_TO_STOCK,
                PropertyReturn::PURPOSE_OTHER,
            ])],
            'received_from' => ['nullable', 'string', 'max:255'],
            'returned_to' => ['nullable', 'string', 'max:255'],
            'end_user' => ['nullable', 'string', 'max:255'],
            'certification' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'integer', 'distinct', 'exists:accountable_items,id'],
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string', 'max:2000'],
        ];
    }
}