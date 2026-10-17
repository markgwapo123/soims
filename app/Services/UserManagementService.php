<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function create(array $attributes, User $assignedBy, ?string $ipAddress = null, ?string $userAgent = null): User
    {
        return DB::transaction(function () use ($attributes, $assignedBy, $ipAddress, $userAgent): User {
            $roleKey = $attributes['role_key'];
            unset($attributes['role_key']);

            $user = User::create($attributes);
            $this->assignRole($user, $roleKey, $assignedBy);

            $user->load('roles');
            $this->audit->record($assignedBy, 'created', 'users', $user, null, [
                'name' => $user->name, 'email' => $user->email,
                'role_keys' => $user->roles->pluck('key')->all(),
            ], null, $ipAddress, $userAgent);

            return $user;
        });
    }

    public function update(User $user, array $attributes, User $changedBy, ?string $ipAddress = null, ?string $userAgent = null): User
    {
        return DB::transaction(function () use ($user, $attributes, $changedBy, $ipAddress, $userAgent): User {
            $previous = $user->only(['name', 'email', 'department_id', 'office_id', 'is_active']);
            $previous['role_keys'] = $user->roles()->wherePivotNull('revoked_at')->pluck('roles.key')->all();
            $roleKey = $attributes['role_key'] ?? null;
            unset($attributes['role_key']);

            $user->fill($attributes)->save();

            if ($roleKey !== null && ! $user->hasRole($roleKey)) {
                DB::table('user_roles')
                    ->where('user_id', $user->id)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now(), 'updated_at' => now()]);
                $this->assignRole($user, $roleKey, $changedBy);
            }

            $updated = $user->fresh(['roles']);
            $this->audit->record($changedBy, 'updated', 'users', $updated, $previous, [
                'name' => $updated->name, 'email' => $updated->email,
                'department_id' => $updated->department_id, 'office_id' => $updated->office_id,
                'is_active' => $updated->is_active, 'role_keys' => $updated->roles->pluck('key')->all(),
            ], null, $ipAddress, $userAgent);

            return $updated;
        });
    }

    public function deactivate(User $user, User $changedBy, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($user, $changedBy, $ipAddress, $userAgent): void {
            $previous = ['is_active' => $user->is_active];
            $user->forceFill(['is_active' => false])->save();
            DB::table('user_roles')
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'updated_at' => now()]);
            $this->audit->record($changedBy, 'deactivated', 'users', $user, $previous, ['is_active' => false], null, $ipAddress, $userAgent);
        });
    }

    public function activate(User $user, User $changedBy, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($user, $changedBy, $ipAddress, $userAgent): void {
            $previous = ['is_active' => $user->is_active];
            $user->forceFill(['is_active' => true])->save();
            $this->audit->record($changedBy, 'activated', 'users', $user, $previous, ['is_active' => true], null, $ipAddress, $userAgent);
        });
    }

    public function resetPassword(User $user, string $password, User $changedBy, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($user, $password, $changedBy, $ipAddress, $userAgent): void {
            $user->forceFill(['password' => $password])->save();
            $this->audit->record($changedBy, 'password_reset', 'users', $user, null, ['password_reset' => true], null, $ipAddress, $userAgent);
        });
    }

    private function assignRole(User $user, string $roleKey, User $assignedBy): void
    {
        $role = Role::where('key', $roleKey)->where('is_active', true)->firstOrFail();

        $user->roles()->attach($role, [
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
        ]);
    }
}
