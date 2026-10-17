<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Role;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyRequestTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $category = Category::create(['code' => 'PAPER', 'name' => 'Paper', 'is_active' => true]);
        $this->item = Item::create([
            'stock_no' => 'PAPER_A4', 'name' => 'A4 Paper', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'default_unit_price' => 250, 'is_active' => true,
        ]);
        Inventory::create(['item_id' => $this->item->id, 'quantity_on_hand' => 3, 'quantity_reserved' => 0]);
    }

    public function test_academic_department_request_is_routed_as_academic(): void
    {
        $user = $this->requester('academic');

        $this->actingAs($user)->post(route('requests.store'), $this->payload(10))
            ->assertRedirect();

        $request = SupplyRequest::firstOrFail();
        $this->assertSame('academic', $request->request_type);
        $this->assertSame('submitted', $request->status);
        $this->assertSame($user->id, $request->requestor_id);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{6}$/', $request->request_no);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id, 'action' => 'submitted', 'module' => 'requests',
            'record_type' => SupplyRequest::class, 'record_id' => $request->id,
        ]);
    }

    public function test_non_academic_department_request_is_routed_as_non_academic(): void
    {
        $this->actingAs($this->requester('non_academic'))->post(route('requests.store'), $this->payload(2));

        $this->assertSame('non_academic', SupplyRequest::firstOrFail()->request_type);
    }

    public function test_requested_quantity_is_preserved_even_when_stock_is_lower(): void
    {
        $this->actingAs($this->requester('academic'))->post(route('requests.store'), $this->payload(10));

        $line = SupplyRequest::firstOrFail()->items()->firstOrFail();
        $this->assertSame('10.000', $line->requested_quantity);
        $this->assertSame('250.00', $line->unit_price_snapshot);
        $this->assertSame('0.000', $line->issued_quantity);
    }

    public function test_request_requires_at_least_one_item_with_quantity(): void
    {
        $this->actingAs($this->requester('academic'))->from(route('requests.create'))
            ->post(route('requests.store'), ['purpose' => 'Class use', 'items' => [['item_id' => $this->item->id, 'quantity' => '']]])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, SupplyRequest::count());
    }

    public function test_user_without_department_cannot_submit(): void
    {
        $user = User::factory()->create();
        $this->assign($user, 'program_department');

        $this->actingAs($user)->from(route('requests.create'))->post(route('requests.store'), $this->payload(1))
            ->assertSessionHasErrors('purpose');

        $this->assertSame(0, SupplyRequest::count());
    }

    public function test_users_only_see_their_own_requests(): void
    {
        $owner = $this->requester('academic');
        $other = $this->requester('academic');
        $this->actingAs($owner)->post(route('requests.store'), $this->payload(1));
        $request = SupplyRequest::firstOrFail();

        $this->actingAs($owner)->get(route('requests.show', $request))->assertOk()->assertSee($request->request_no);
        $this->actingAs($other)->get(route('requests.show', $request))->assertForbidden();
        $this->actingAs($other)->get(route('requests.index'))->assertOk()->assertDontSee($request->request_no);
    }

    public function test_requests_require_submit_permission(): void
    {
        $user = User::factory()->create();
        $this->assign($user, 'supply_sub_administrator');

        $this->actingAs($user)->get(route('requests.index'))->assertForbidden();
    }

    private function requester(string $departmentType): User
    {
        $department = Department::create([
            'code' => strtoupper($departmentType).'_'.uniqid(), 'name' => ucfirst($departmentType).' Dept',
            'type' => $departmentType, 'is_active' => true,
        ]);
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->assign($user, 'program_department');

        return $user;
    }

    private function assign(User $user, string $roleKey): void
    {
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);
    }

    private function payload(int $quantity): array
    {
        return ['purpose' => 'Classroom use', 'items' => [['item_id' => $this->item->id, 'quantity' => $quantity]]];
    }
}
