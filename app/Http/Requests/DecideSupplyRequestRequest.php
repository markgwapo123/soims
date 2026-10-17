<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideSupplyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('approve_supply_requests') ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,deny'],
            'remarks' => ['nullable', 'required_if:decision,deny', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return ['remarks.required_if' => 'Remarks are required when denying a request.'];
    }
}
