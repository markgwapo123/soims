<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\Role;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_dashboard_requires_authentication_and_root_redirects_authenticated_users(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('home'))->assertRedirect(route('login'));

        $user = $this->userWithRole('program_department');
        $this->actingAs($user)->get(route('home'))->assertRedirect(route('dashboard'));
    }

    public function test_supply_administrator_sees_live_inventory_receipt_and_audit_data(): void
    {
        [$item] = $this->item(reorderLevel: 5);
        $user = $this->userWithRole('supply_administrator');
        Inventory::create(['item_id' => $item->id, 'quantity_on_hand' => 3, 'quantity_reserved' => 0]);
        InventoryTransaction::create([
            'item_id' => $item->id, 'type' => 'receipt', 'quantity' => 3,
            'reference_type' => 'inventory_receipt', 'performed_by' => $user->id, 'occurred_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $user->id, 'role_snapshot' => 'supply_administrator', 'action' => 'created',
            'module' => 'accountability', 'record_type' => 'AccountableItem', 'record_id' => 1,
            'new_value' => ['property_no' => 'PROP-001'], 'occurred_at' => now(),
        ]);
        $managedUser = User::factory()->create(['name' => 'Managed Supply User']);
        AuditLog::create([
            'user_id' => $user->id, 'role_snapshot' => 'supply_administrator', 'action' => 'created',
            'module' => 'users', 'record_type' => User::class, 'record_id' => $managedUser->id,
            'occurred_at' => now()->addSecond(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total inventory quantity')
            ->assertSee('Low stock items')
            ->assertSee('Manage users')
            ->assertSee('Add Items')
            ->assertDontSee('>My requests</a>', false)
            ->assertDontSee('>New request</a>', false)
            ->assertDontSee('My supply requests')
            ->assertSee('Recent receiving transactions')
            ->assertSee($item->name)
            ->assertSee('Recent activity')
            ->assertSee('Managed Supply User')
            ->assertDontSee('User #'.$managedUser->id)
            ->assertSee('AccountableItem #1');

        $this->actingAs($user)->get(route('dashboard', ['stock_search' => $item->stock_no]))
            ->assertOk()
            ->assertSee($item->name)
            ->assertSee('value="'.$item->stock_no.'"', false);
    }

    public function test_academic_approver_only_sees_academic_requests(): void
    {
        $academic = $this->request('academic', 'submitted');
        $this->request('non_academic', 'submitted');
        $daa = $this->userWithRole('daa');

        $this->actingAs($daa)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($academic->request_no)
            ->assertDontSee('REQ-DASH-NONACADEMIC');
    }

    public function test_request_review_roles_see_read_only_stock_on_dashboard(): void
    {
        foreach (['administrative_officer', 'daa'] as $roleKey) {
            $reviewer = $this->userWithRole($roleKey);

            $this->actingAs($reviewer)->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Stock availability')
                ->assertDontSee('Inventory overview')
                ->assertDontSee('Current stock');
        }
    }

    public function test_program_dashboard_only_shows_own_requests_and_available_stock(): void
    {
        [$item] = $this->item();
        Inventory::create(['item_id' => $item->id, 'quantity_on_hand' => 8, 'quantity_reserved' => 2]);
        for ($index = 1; $index <= 7; $index++) {
            [$additionalItem] = $this->item();
            $additionalItem->update(['name' => 'Dashboard extra supply '.$index]);
            Inventory::create(['item_id' => $additionalItem->id, 'quantity_on_hand' => 1, 'quantity_reserved' => 0]);
        }
        $programUser = $this->userWithRole('program_department');
        $ownRequest = $this->request('academic', 'submitted', $programUser);
        $this->request('academic', 'confirmed');

        $this->actingAs($programUser)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>My requests</a>', false)
            ->assertSee('>New request</a>', false)
            ->assertSee('Submitted requests')
            ->assertSee($ownRequest->request_no)
            ->assertDontSee('REQ-DASH-OTHER')
            ->assertSee('Stock availability')
            ->assertSee('Dashboard extra supply 1');

        $this->actingAs($programUser)->get(route('dashboard', ['stock_page' => 2]))
            ->assertOk()
            ->assertSee($item->name)
            ->assertSee('Dashboard extra supply 7')
            ->assertSee('6');
    }

    public function test_program_dashboard_shows_stock_section_when_inventory_is_empty(): void
    {
        $programUser = $this->userWithRole('program_department');

        $this->actingAs($programUser)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Stock availability')
            ->assertSee('No inventory items found.');
    }

    public function test_sub_administrator_actions_follow_assigned_permissions(): void
    {
        $user = $this->userWithRole('supply_sub_administrator');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Inventory')
            ->assertSee('Add Items')
            ->assertDontSee('Manage users')
            ->assertDontSee('My supply requests');
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }

    private function item(?float $reorderLevel = null): array
    {
        $category = Category::create(['code' => 'DASH-'.uniqid(), 'name' => 'Dashboard supplies', 'is_active' => true]);
        $item = Item::create([
            'stock_no' => 'DASH-'.uniqid(), 'name' => 'Dashboard paper', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'reorder_level' => $reorderLevel, 'is_active' => true,
        ]);

        return [$item];
    }

    private function request(string $type, string $status, ?User $requestor = null): SupplyRequest
    {
        $requestor ??= User::factory()->create();
        $department = Department::create([
            'code' => 'DASH-'.uniqid(), 'name' => 'Dashboard '.$type, 'type' => $type === 'academic' ? 'academic' : 'non_academic', 'is_active' => true,
        ]);
        $tag = $type === 'academic' ? 'ACADEMIC' : ($requestor->roles()->exists() ? 'OWN' : 'OTHER');

        return SupplyRequest::create([
            'request_uuid' => (string) Str::uuid(), 'request_no' => 'REQ-DASH-'.$tag.'-'.uniqid(),
            'requestor_id' => $requestor->id, 'department_id' => $department->id,
            'request_type' => $type, 'purpose' => 'Dashboard test request', 'status' => $status,
            'submitted_at' => now(), 'confirmed_at' => $status === 'confirmed' ? now() : null,
        ]);
    }
}