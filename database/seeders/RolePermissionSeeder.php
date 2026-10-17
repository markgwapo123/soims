<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['key' => 'supply_administrator', 'name' => 'Supply Administrator'],
            ['key' => 'supply_sub_administrator', 'name' => 'Supply Sub-Administrator'],
            ['key' => 'administrative_officer', 'name' => 'Administrative Officer'],
            ['key' => 'daa', 'name' => 'Department of Academic Affairs'],
            ['key' => 'program_department', 'name' => 'Program / Department'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['key' => $role['key']], $role + ['is_active' => true]);
        }

        $permissions = [
            ['key' => 'access_supply_office', 'name' => 'Access Supply Office', 'module' => 'system'],
            ['key' => 'manage_users', 'name' => 'Manage Users', 'module' => 'users'],
            ['key' => 'manage_catalog', 'name' => 'Manage Catalog', 'module' => 'catalog'],
            ['key' => 'manage_inventory', 'name' => 'Manage Inventory', 'module' => 'inventory'],
            ['key' => 'submit_supply_requests', 'name' => 'Submit Supply Requests', 'module' => 'requests'],
            ['key' => 'approve_supply_requests', 'name' => 'Approve Supply Requests', 'module' => 'requests'],
            ['key' => 'process_procurement', 'name' => 'Process Procurement', 'module' => 'procurement'],
            ['key' => 'issue_supplies', 'name' => 'Issue Supplies', 'module' => 'issuance'],
            ['key' => 'manage_accountable_items', 'name' => 'Manage Accountable Items', 'module' => 'accountability'],
            ['key' => 'view_reports', 'name' => 'View Reports', 'module' => 'reports'],
            ['key' => 'view_audit_logs', 'name' => 'View Audit Logs', 'module' => 'audit'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['key' => $permission['key']], $permission);
        }

        $rolePermissions = [
            'supply_administrator' => array_column($permissions, 'key'),
            'supply_sub_administrator' => ['access_supply_office', 'manage_catalog', 'manage_inventory', 'issue_supplies', 'view_reports'],
            'administrative_officer' => ['access_supply_office', 'approve_supply_requests', 'view_reports'],
            'daa' => ['access_supply_office', 'approve_supply_requests', 'view_reports'],
            'program_department' => ['submit_supply_requests'],
        ];

        foreach ($rolePermissions as $roleKey => $permissionKeys) {
            $role = Role::where('key', $roleKey)->firstOrFail();
            $role->permissions()->sync(Permission::whereIn('key', $permissionKeys)->pluck('id'));
        }
    }
}
