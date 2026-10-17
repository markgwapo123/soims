<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'type', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function offices(): HasMany { return $this->hasMany(Office::class); }
}
