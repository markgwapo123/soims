<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ModifyConfirmedRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Gate::forUser($this->user())->allows('modify', $this->route('supplyRequest'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.confirmed_quantity' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'lines.*.confirmed_item_id' => ['nullable', 'integer', 'exists:items,id'],
        ];
    }

    public function messages(): array
    {
        return ['reason.required' => 'A reason is required for every modification.'];
    }
}
