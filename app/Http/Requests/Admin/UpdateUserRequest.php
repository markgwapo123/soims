<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_users') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:users,email,'.$this->route('user')->id],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'office_id' => ['sometimes', 'nullable', 'integer', 'exists:offices,id'],
            'role_key' => ['sometimes', 'string', 'exists:roles,key'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
