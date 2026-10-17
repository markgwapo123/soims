<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_audit_log_viewer_requires_view_audit_logs_permission(): void
    {
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
        $this->actingAs($this->userWithRole('administrative_officer'))->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($this->userWithRole('supply_administrator'))->get(route('audit-logs.index'))->assertOk();
    }

    public function test_audit_log_filters_and_detail_show_actor_role_and_before_after_data(): void
    {
        $actor = $this->userWithRole('supply_administrator');
        $matching = $this->log($actor, ['action' => 'updated', 'module' => 'inventory', 'record_id' => 42]);
        $this->log($actor, ['action' => 'created', 'module' => 'catalog', 'record_id' => 7]);

        $this->actingAs($actor)->get(route('audit-logs.index', [
            'module' => 'inventory', 'action' => 'updated', 'record_id' => 42,
            'from' => '2026-10-03', 'to' => '2026-10-03',
        ]))->assertOk()->assertSee('Inventory')->assertSee('#42')->assertDontSee('Catalog #7');

        $this->get(route('audit-logs.show', $matching))->assertOk()
            ->assertSee('supply_administrator')->assertSee('quantity_on_hand')->assertSee('9.000')->assertSee('10.000');
    }

    private function log(User $actor, array $attributes): AuditLog
    {
        return AuditLog::create($attributes + [
            'user_id' => $actor->id,
            'role_snapshot' => 'supply_administrator',
            'action' => 'updated',
            'module' => 'inventory',
            'record_type' => 'App\\Models\\Inventory',
            'record_id' => 42,
            'previous_value' => ['quantity_on_hand' => '9.000'],
            'new_value' => ['quantity_on_hand' => '10.000'],
            'reason' => 'Test event',
            'occurred_at' => '2026-10-03 12:00:00',
        ]);
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }
}