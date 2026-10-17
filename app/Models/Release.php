<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Release extends Model
{
    protected $fillable = ['ris_id', 'released_by', 'received_by', 'released_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function ris(): BelongsTo { return $this->belongsTo(Ris::class); }

    public function releaser(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }

    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}