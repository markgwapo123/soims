<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_catalog_manager_can_create_update_and_toggle_catalog_records(): void
    {
        $admin = User::factory()->create();
        $this->assignRole($admin, 'supply_administrator');

        $this->actingAs($admin)->post(route('admin.catalog.departments.store'), [
            'code' => 'DAA', 'name' => 'Academic Affairs', 'type' => 'academic',
        ])->assertRedirect(route('admin.catalog.index'));

        $department = Department::where('code', 'DAA')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.catalog.offices.store'), [
            'code' => 'SUPPLY', 'name' => 'Supply Office', 'department_id' => $department->id,
        ])->assertRedirect(route('admin.catalog.index'));

        $this->actingAs($admin)->post(route('admin.catalog.categories.store'), [
            'code' => 'PAPER', 'name' => 'Paper Supplies',
        ])->assertRedirect(route('admin.catalog.index'));

        $category = Category::where('code', 'PAPER')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.catalog.items.store'), [
            'stock_no' => 'PAPER_A4', 'name' => 'A4 Bond Paper', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'default_unit_price' => 250,
        ])->assertRedirect(route('admin.catalog.index'));

        $this->assertDatabaseHas('items', ['stock_no' => 'PAPER_A4', 'classification' => 'consumable']);
        $this->actingAs($admin)->put(route('admin.catalog.departments.update', $department), [
            'code' => 'DAA', 'name' => 'Department of Academic Affairs', 'type' => 'academic',
        ])->assertRedirect(route('admin.catalog.index'));
        $this->assertSame('Department of Academic Affairs', $department->fresh()->name);

        $this->actingAs($admin)->post(route('admin.catalog.deactivate', ['departments', $department->id]))->assertRedirect(route('admin.catalog.index'));
        $this->assertFalse($department->fresh()->is_active);
        $this->actingAs($admin)->post(route('admin.catalog.activate', ['departments', $department->id]))->assertRedirect(route('admin.catalog.index'));
        $this->assertTrue($department->fresh()->is_active);

        $this->assertSame(7, AuditLog::where('module', 'catalog')->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id, 'module' => 'catalog', 'action' => 'created',
            'record_type' => Department::class, 'record_id' => $department->id,
        ]);
    }

    public function test_catalog_requires_manage_catalog_permission(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'program_department');

        $this->actingAs($user)->get(route('admin.catalog.index'))->assertForbidden();
    }

    private function assignRole(User $user, string $roleKey): void
    {
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);
    }
}
