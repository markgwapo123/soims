<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_admin_can_create_update_and_deactivate_a_user(): void
    {
        $admin = User::factory()->create();
        $this->assignRole($admin, 'supply_administrator');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Stock Custodian',
            'username' => 'stock_custodian',
            'email' => 'stock@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_key' => 'program_department',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('username', 'stock_custodian')->firstOrFail();
        $this->assertTrue($user->hasRole('program_department'));

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Updated Custodian',
            'role_key' => 'supply_sub_administrator',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('Updated Custodian', $user->fresh()->name);
        $this->assertTrue($user->fresh()->hasRole('supply_sub_administrator'));
        $this->assertFalse($user->fresh()->hasRole('program_department'));

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($user->fresh()->is_active);
        $this->assertFalse($user->fresh()->hasRole('supply_sub_administrator'));

        $this->actingAs($admin)->post(route('admin.users.activate', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($user->fresh()->is_active);

        $this->actingAs($admin)->put(route('admin.users.password', $user), [
            'password' => 'reset-password',
            'password_confirmation' => 'reset-password',
        ])->assertRedirect(route('admin.users.edit', $user));

        $this->assertTrue(Hash::check('reset-password', $user->fresh()->password));
        $this->assertSame(5, AuditLog::where('module', 'users')->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id, 'module' => 'users', 'action' => 'password_reset',
            'record_type' => User::class, 'record_id' => $user->id,
        ]);
        $this->assertStringNotContainsString('reset-password', json_encode(AuditLog::where('module', 'users')->get()->toArray()));
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create();
        $this->assignRole($admin, 'supply_administrator');

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
            ->assertStatus(422);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_email_is_required_when_creating_a_user(): void
    {
        $admin = User::factory()->create();
        $this->assignRole($admin, 'supply_administrator');

        $this->actingAs($admin)->from(route('admin.users.create'))->post(route('admin.users.store'), [
            'name' => 'No Email', 'username' => 'no_email', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role_key' => 'program_department',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['username' => 'no_email']);
    }

    public function test_admin_can_assign_department_and_office_to_a_user(): void
    {
        $admin = User::factory()->create();
        $this->assignRole($admin, 'supply_administrator');
        $department = \App\Models\Department::create(['code' => 'DAA', 'name' => 'Academic Affairs', 'type' => 'academic', 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk()->assertSee('Academic Affairs');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Dept User', 'username' => 'dept_user', 'email' => 'dept@example.test', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role_key' => 'program_department', 'department_id' => $department->id,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame($department->id, User::where('username', 'dept_user')->firstOrFail()->department_id);
    }

    private function assignRole(User $user, string $roleKey): void
    {
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), [
            'assigned_at' => now(),
        ]);
    }
}
