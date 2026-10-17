<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = ['stock_no', 'name', 'description', 'category_id', 'classification', 'unit_of_measure', 'default_unit_price', 'reorder_level', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean', 'default_unit_price' => 'decimal:2', 'reorder_level' => 'decimal:3']; }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }

    public function inventory(): HasOne { return $this->hasOne(Inventory::class); }
}
