<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CatalogManagementService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function create(Model $model, array $attributes, \App\Models\User $actor, ?string $ipAddress = null, ?string $userAgent = null): Model
    {
        return DB::transaction(function () use ($model, $attributes, $actor, $ipAddress, $userAgent): Model {
            $created = $model::create($attributes);
            $this->audit->record($actor, 'created', 'catalog', $created, null, $created->only(array_keys($attributes)), null, $ipAddress, $userAgent);
            return $created;
        });
    }

    public function update(Model $model, array $attributes, \App\Models\User $actor, ?string $ipAddress = null, ?string $userAgent = null): Model
    {
        return DB::transaction(function () use ($model, $attributes, $actor, $ipAddress, $userAgent): Model {
            $previous = $model->only(array_keys($attributes));
            $model->fill($attributes)->save();
            $updated = $model->fresh();
            $this->audit->record($actor, 'updated', 'catalog', $updated, $previous, $updated->only(array_keys($attributes)), null, $ipAddress, $userAgent);
            return $updated;
        });
    }

    public function deactivate(Model $model, \App\Models\User $actor, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($model, $actor, $ipAddress, $userAgent): void {
            $previous = ['is_active' => $model->is_active];
            $model->forceFill(['is_active' => false])->save();
            $this->audit->record($actor, 'deactivated', 'catalog', $model, $previous, ['is_active' => false], null, $ipAddress, $userAgent);
        });
    }

    public function activate(Model $model, \App\Models\User $actor, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($model, $actor, $ipAddress, $userAgent): void {
            $previous = ['is_active' => $model->is_active];
            $model->forceFill(['is_active' => true])->save();
            $this->audit->record($actor, 'activated', 'catalog', $model, $previous, ['is_active' => true], null, $ipAddress, $userAgent);
        });
    }
}
