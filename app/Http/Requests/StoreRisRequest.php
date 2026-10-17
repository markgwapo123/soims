<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('issue_supplies') ?? false;
    }

    public function rules(): array
    {
        return [
            'fund_cluster' => ['nullable', 'string', 'max:100'],
            'responsibility_center_code' => ['nullable', 'string', 'max:100'],
            'received_printed_name' => ['nullable', 'string', 'max:255'],
            'received_designation' => ['nullable', 'string', 'max:255'],
            'issue' => ['nullable', 'array'],
            'issue.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ];
    }
}
