<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->command === null) {
            throw new RuntimeException('Run this seeder from an interactive Artisan command.');
        }

        $attributes = [
            'name' => $this->command->ask('Admin name'),
            'username' => $this->command->ask('Admin username'),
            'email' => $this->command->ask('Admin email'),
            'password' => $this->command->secret('Admin password'),
        ];
        $passwordConfirmation = $this->command->secret('Confirm admin password');

        if ($attributes['password'] !== $passwordConfirmation) {
            throw new RuntimeException('The passwords do not match.');
        }

        Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        $this->call(RolePermissionSeeder::class);
        $adminRole = Role::where('key', 'supply_administrator')->firstOrFail();

        DB::transaction(function () use ($attributes, $adminRole): void {
            $adminAlreadyExists = DB::table('user_roles')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('roles.key', 'supply_administrator')
                ->whereNull('user_roles.revoked_at')
                ->exists();

            if ($adminAlreadyExists) {
                throw new RuntimeException('A supply administrator already exists.');
            }

            $user = User::create($attributes + ['is_active' => true]);
            $user->roles()->attach($adminRole->id, [
                'assigned_by' => null,
                'assigned_at' => now(),
            ]);
        });

        $this->command->info('Initial supply administrator created.');
    }
}