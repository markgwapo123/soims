<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Item;
use App\Models\RequestModification;
use App\Models\Role;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequestModificationTest extends TestCase
{
    use RefreshDatabase;

    private Item $paper;
    private Item $pencil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $category = Category::create(['code' => 'SUP', 'name' => 'Supplies', 'is_active' => true]);
        $this->paper = $this->makeItem($category, 'PAPER_A4', 'A4 Paper');
        $this->pencil = $this->makeItem($category, 'PENCIL', 'Pencil');
    }

    public function test_quantity_change_preserves_original_and_records_history(): void
    {
        $request = $this->confirmedRequest('academic', 10);
        $daa = $this->userWithRole('daa');

        $this->actingAs($daa)->put(route('modifications.update', $request), $this->payload($request, 8, $this->paper->id, 'Budget cut'))
            ->assertRedirect(route('modifications.edit', $request));

        $line = $request->items()->firstOrFail();
        $this->assertSame('10.000', $line->requested_quantity);
        $this->assertSame('8.000', $line->confirmed_quantity);

        $this->assertDatabaseHas('request_modifications', [
            'supply_request_id' => $request->id, 'field_name' => 'confirmed_quantity',
            'modified_by' => $daa->id, 'role_snapshot' => 'daa', 'reason' => 'Budget cut',
        ]);
        $modification = RequestModification::firstOrFail();
        $this->assertSame(['quantity' => '10.000'], $modification->previous_value);
        $this->assertSame(['quantity' => '8.000'], $modification->new_value);
        $this->assertDatabaseHas('notifications', ['user_id' => $request->requestor_id, 'type' => 'request_modified']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $daa->id, 'action' => 'modified', 'module' => 'requests',
            'record_type' => SupplyRequest::class, 'record_id' => $request->id, 'reason' => 'Budget cut',
        ]);
    }

    public function test_item_change_keeps_the_original_item(): void
    {
        $request = $this->confirmedRequest('academic', 10);

        $this->actingAs($this->userWithRole('daa'))
            ->put(route('modifications.update', $request), $this->payload($request, 10, $this->pencil->id, 'Out of paper'));

        $line = $request->items()->firstOrFail();
        $this->assertSame($this->paper->id, $line->item_id);
        $this->assertSame($this->pencil->id, $line->confirmed_item_id);

        $modification = RequestModification::where('field_name', 'confirmed_item_id')->firstOrFail();
        $this->assertSame($this->paper->id, $modification->previous_value['item_id']);
        $this->assertSame($this->pencil->id, $modification->new_value['item_id']);
    }

    public function test_successive_modifications_keep_the_full_history(): void
    {
        $request = $this->confirmedRequest('academic', 10);
        $daa = $this->userWithRole('daa');

        $this->actingAs($daa)->put(route('modifications.update', $request), $this->payload($request, 8, $this->paper->id, 'First'));
        $this->actingAs($daa)->put(route('modifications.update', $request), $this->payload($request, 5, $this->paper->id, 'Second'));

        $history = $request->modifications()->get();
        $this->assertCount(2, $history);
        $this->assertSame(['quantity' => '10.000'], $history[0]->previous_value);
        $this->assertSame(['quantity' => '8.000'], $history[0]->new_value);
        $this->assertSame(['quantity' => '8.000'], $history[1]->previous_value);
        $this->assertSame(['quantity' => '5.000'], $history[1]->new_value);
        $this->assertSame('10.000', $request->items()->firstOrFail()->requested_quantity);
    }

    public function test_reason_is_required_and_unchanged_submissions_are_rejected(): void
    {
        $request = $this->confirmedRequest('academic', 10);
        $daa = $this->userWithRole('daa');

        $this->actingAs($daa)->from(route('modifications.edit', $request))
            ->put(route('modifications.update', $request), $this->payload($request, 5, $this->paper->id, ''))
            ->assertSessionHasErrors('reason');

        $this->actingAs($daa)->from(route('modifications.edit', $request))
            ->put(route('modifications.update', $request), $this->payload($request, 10, $this->paper->id, 'No change'))
            ->assertSessionHasErrors('lines');

        $this->assertSame(0, RequestModification::count());
        $this->assertSame('10.000', $request->items()->firstOrFail()->confirmed_quantity);
    }

    public function test_approver_cannot_modify_the_other_request_type(): void
    {
        $nonAcademic = $this->confirmedRequest('non_academic', 10);

        $this->actingAs($this->userWithRole('daa'))->get(route('modifications.edit', $nonAcademic))->assertForbidden();
        $this->actingAs($this->userWithRole('daa'))
            ->put(route('modifications.update', $nonAcademic), $this->payload($nonAcademic, 1, $this->paper->id, 'x'))
            ->assertForbidden();
    }

    public function test_only_confirmed_requests_can_be_modified(): void
    {
        $request = $this->confirmedRequest('academic', 10);
        $request->update(['status' => 'submitted']);

        $this->actingAs($this->userWithRole('daa'))->get(route('modifications.edit', $request))->assertForbidden();
    }

    public function test_supply_office_staff_can_modify_but_requesters_cannot(): void
    {
        $request = $this->confirmedRequest('non_academic', 10);

        $this->actingAs($this->userWithRole('supply_sub_administrator'))
            ->put(route('modifications.update', $request), $this->payload($request, 4, $this->paper->id, 'Stock shortage'))
            ->assertRedirect();
        $this->assertSame('4.000', $request->items()->firstOrFail()->confirmed_quantity);

        $this->actingAs($this->userWithRole('program_department'))->get(route('modifications.index'))->assertForbidden();
        $this->actingAs($request->requestor)->get(route('modifications.edit', $request))->assertForbidden();
    }

    public function test_requester_sees_confirmed_values_and_history(): void
    {
        $request = $this->confirmedRequest('academic', 10);
        $this->actingAs($this->userWithRole('daa'))
            ->put(route('modifications.update', $request), $this->payload($request, 6, $this->paper->id, 'Shared stock'));

        $this->actingAs($request->requestor)->get(route('requests.show', $request))
            ->assertOk()->assertSee('Shared stock')->assertSee('6.000');
    }

    private function makeItem(Category $category, string $stockNo, string $name): Item
    {
        return Item::create([
            'stock_no' => $stockNo, 'name' => $name, 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'pc', 'is_active' => true,
        ]);
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }

    private function confirmedRequest(string $type, int $quantity): SupplyRequest
    {
        $department = Department::create(['code' => strtoupper($type).'_'.uniqid(), 'name' => $type.' dept', 'type' => $type, 'is_active' => true]);

        $request = SupplyRequest::create([
            'request_uuid' => (string) Str::uuid(), 'request_no' => 'REQ-TEST-'.uniqid(),
            'requestor_id' => $this->userWithRole('program_department')->id, 'department_id' => $department->id,
            'request_type' => $type, 'purpose' => 'Test', 'status' => 'confirmed',
            'submitted_at' => now(), 'confirmed_at' => now(),
        ]);
        $request->items()->create(['item_id' => $this->paper->id, 'requested_quantity' => $quantity, 'confirmed_quantity' => $quantity]);

        return $request;
    }

    private function payload(SupplyRequest $request, int $quantity, int $itemId, string $reason): array
    {
        return [
            'reason' => $reason,
            'lines' => [['id' => $request->items()->firstOrFail()->id, 'confirmed_quantity' => $quantity, 'confirmed_item_id' => $itemId]],
        ];
    }
}
