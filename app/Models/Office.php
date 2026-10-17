<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Office extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'department_id', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
}
