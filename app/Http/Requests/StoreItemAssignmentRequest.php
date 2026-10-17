<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreItemAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_accountable_items') ?? false;
    }

    public function rules(): array
    {
        return [
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_to_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'assigned_to_office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'location' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('assigned_to_user_id')
                && ! $this->filled('assigned_to_department_id')
                && ! $this->filled('assigned_to_office_id')) {
                $validator->errors()->add('assigned_to_user_id', 'Select a user, department, or office for this assignment.');
            }
        }];
    }
}