<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseRisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('issue_supplies') ?? false;
    }

    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:2000']];
    }
}