<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestModification extends Model
{
    protected $fillable = [
        'supply_request_id', 'supply_request_item_id', 'field_name', 'previous_value', 'new_value',
        'reason', 'modified_by', 'role_snapshot', 'modified_at',
    ];

    protected function casts(): array
    {
        return ['previous_value' => 'array', 'new_value' => 'array', 'modified_at' => 'datetime'];
    }

    public function supplyRequest(): BelongsTo { return $this->belongsTo(SupplyRequest::class); }

    public function modifier(): BelongsTo { return $this->belongsTo(User::class, 'modified_by'); }

    public function fieldLabel(): string
    {
        return $this->field_name === 'confirmed_quantity' ? 'Quantity' : 'Item';
    }

    public function describe(array $value): string
    {
        return $value['quantity'] ?? trim(($value['stock_no'] ?? '').' '.($value['name'] ?? ''));
    }
}
