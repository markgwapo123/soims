<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_authorization_endpoints(): void
    {
        $this->get('/authorization/role-check')->assertRedirect(route('login'));
    }

    public function test_matching_role_can_access_role_protected_route(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'supply_administrator');

        $this->actingAs($user)->get('/authorization/role-check')
            ->assertOk()
            ->assertSee('role authorized');
    }

    public function test_non_matching_role_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'program_department');

        $this->actingAs($user)->get('/authorization/role-check')->assertForbidden();
    }

    public function test_permission_grants_access_independently_of_role_name(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'supply_sub_administrator');

        $this->actingAs($user)->get('/authorization/permission-check')
            ->assertOk()
            ->assertSee('permission authorized');
    }

    public function test_revoked_role_is_not_authorized(): void
    {
        $user = User::factory()->create();
        $role = Role::where('key', 'supply_administrator')->firstOrFail();
        $user->roles()->attach($role, [
            'assigned_at' => now()->subDay(),
            'revoked_at' => now(),
        ]);

        $this->actingAs($user)->get('/authorization/role-check')->assertForbidden();
    }

    public function test_user_management_requires_manage_users_permission(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'program_department');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_catalog_contains_exactly_the_five_approved_roles(): void
    {
        $this->assertSame([
            'administrative_officer',
            'daa',
            'program_department',
            'supply_administrator',
            'supply_sub_administrator',
        ], Role::orderBy('key')->pluck('key')->all());
    }

    public function test_supply_sub_administrator_permissions_are_configurable(): void
    {
        $user = User::factory()->create();
        $role = Role::where('key', 'supply_sub_administrator')->firstOrFail();
        $permission = Permission::where('key', 'manage_users')->firstOrFail();
        $user->roles()->attach($role, ['assigned_at' => now()]);

        $this->assertFalse($user->hasPermission('manage_users'));

        $role->permissions()->attach($permission);

        $this->assertTrue($user->fresh()->hasPermission('manage_users'));
    }

    private function assignRole(User $user, string $roleKey): void
    {
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), [
            'assigned_at' => now(),
        ]);
    }
}
