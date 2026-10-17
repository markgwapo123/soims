<?php

namespace Tests\Feature;

use App\Models\AccountableItem;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\ItemAssignment;
use App\Models\ItemTransfer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountableItemTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private AccountableItem $asset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $category = Category::create(['code' => 'ASSET', 'name' => 'Equipment', 'is_active' => true]);
        $item = Item::create([
            'stock_no' => 'LAPTOP-01', 'name' => 'Laptop', 'category_id' => $category->id,
            'classification' => 'accountable', 'unit_of_measure' => 'unit', 'is_active' => true,
        ]);
        $department = Department::create(['code' => 'IT', 'name' => 'Information Technology', 'type' => 'administrative', 'is_active' => true]);
        $custodian = User::factory()->create(['name' => 'Alex Custodian']);

        $this->asset = AccountableItem::create([
            'item_id' => $item->id,
            'property_no' => 'WVSU-2026-0001',
            'barcode' => '0123456789012',
            'serial_no' => 'SERIAL-001',
            'date_acquired' => '2026-09-15',
            'unit_value' => 45000,
            'status' => 'assigned',
            'current_department_id' => $department->id,
            'current_user_id' => $custodian->id,
            'current_location' => 'Room 204',
        ]);
    }

    public function test_authorized_staff_can_find_an_asset_by_scanned_barcode(): void
    {
        $this->actingAs($this->userWithRole('supply_administrator'))
            ->get(route('accountable-items.index', ['search' => $this->asset->barcode]))
            ->assertOk()
            ->assertSee($this->asset->property_no)
            ->assertSee('Laptop')
            ->assertSee('Alex Custodian');
    }

    public function test_asset_can_be_found_by_property_or_serial_number(): void
    {
        $staff = $this->userWithRole('supply_administrator');

        foreach ([$this->asset->property_no, $this->asset->serial_no] as $identifier) {
            $this->actingAs($staff)
                ->get(route('accountable-items.index', ['search' => $identifier]))
                ->assertOk()
                ->assertSee($this->asset->barcode);
        }
    }

    public function test_asset_detail_shows_current_custodian_and_location(): void
    {
        $this->actingAs($this->userWithRole('supply_administrator'))
            ->get(route('accountable-items.show', $this->asset))
            ->assertOk()
            ->assertSee('SERIAL-001')
            ->assertSee('Information Technology')
            ->assertSee('Room 204')
            ->assertSee('Alex Custodian');
    }

    public function test_asset_lookup_requires_manage_accountable_items_permission(): void
    {
        $this->get(route('accountable-items.index'))->assertRedirect(route('login'));
        $this->actingAs($this->userWithRole('program_department'))
            ->get(route('accountable-items.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('program_department'))
            ->post(route('accountable-items.assign', $this->asset), ['location' => 'Room 101'])
            ->assertForbidden();
    }

    public function test_authorized_staff_registers_an_accountable_unit_and_audit_event(): void
    {
        $staff = $this->userWithRole('supply_administrator');
        $payload = [
            'item_id' => $this->asset->item_id,
            'property_no' => 'WVSU-2026-0002',
            'barcode' => '0987654321098',
            'serial_no' => 'SERIAL-002',
            'date_acquired' => '2026-09-20',
            'unit_value' => 52000,
            'status' => 'available',
        ];

        $response = $this->actingAs($staff)->post(route('accountable-items.store'), $payload);

        $registered = AccountableItem::where('property_no', 'WVSU-2026-0002')->firstOrFail();
        $response->assertRedirect(route('accountable-items.show', $registered));
        $this->assertSame('0987654321098', $registered->barcode);
        $this->assertSame('available', $registered->status);

        $audit = AuditLog::where('record_type', AccountableItem::class)->where('record_id', $registered->id)->firstOrFail();
        $this->assertSame($staff->id, $audit->user_id);
        $this->assertSame('created', $audit->action);
        $this->assertSame('accountability', $audit->module);
        $this->assertSame($payload['property_no'], $audit->new_value['property_no']);
        $this->assertNull($audit->previous_value);
    }

    public function test_registration_rejects_duplicate_property_or_barcode(): void
    {
        $this->actingAs($this->userWithRole('supply_administrator'))
            ->from(route('accountable-items.create'))
            ->post(route('accountable-items.store'), [
                'item_id' => $this->asset->item_id,
                'property_no' => $this->asset->property_no,
                'barcode' => $this->asset->barcode,
                'status' => 'available',
            ])
            ->assertSessionHasErrors(['property_no', 'barcode']);

        $this->assertSame(1, AccountableItem::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_registration_rejects_catalog_items_not_classified_as_accountable(): void
    {
        $consumable = Item::create([
            'stock_no' => 'PAPER-A4', 'name' => 'A4 Paper', 'category_id' => $this->asset->item->category_id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'is_active' => true,
        ]);

        $this->actingAs($this->userWithRole('supply_administrator'))
            ->from(route('accountable-items.create'))
            ->post(route('accountable-items.store'), [
                'item_id' => $consumable->id,
                'property_no' => 'WVSU-2026-0002',
                'barcode' => '0987654321098',
                'status' => 'available',
            ])
            ->assertSessionHasErrors('item_id');

        $this->assertSame(1, AccountableItem::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_authorized_staff_can_assign_an_asset_and_audit_the_change(): void
    {
        $staff = $this->userWithRole('supply_administrator');
        $assignee = User::factory()->create(['name' => 'New Custodian']);
        $previousUserId = $this->asset->current_user_id;

        $this->actingAs($staff)->post(route('accountable-items.assign', $this->asset), [
            'assigned_to_user_id' => $assignee->id,
            'assigned_to_department_id' => $this->asset->current_department_id,
            'location' => 'Room 305',
        ])->assertRedirect(route('accountable-items.show', $this->asset));

        $assignment = ItemAssignment::firstOrFail();
        $asset = $this->asset->fresh();
        $this->assertSame($assignee->id, $asset->current_user_id);
        $this->assertSame('Room 305', $asset->current_location);
        $this->assertSame('assigned', $asset->status);
        $this->assertSame(ItemAssignment::STATUS_ACTIVE, $assignment->status);
        $this->assertNull($assignment->released_at);

        $audit = AuditLog::where('action', 'assigned')->firstOrFail();
        $this->assertSame($previousUserId, $audit->previous_value['current_user_id']);
        $this->assertSame($assignee->id, $audit->new_value['current_user_id']);
    }

    public function test_asset_cannot_have_a_second_active_assignment(): void
    {
        $staff = $this->userWithRole('supply_administrator');
        $firstAssignee = User::factory()->create();
        $secondAssignee = User::factory()->create();
        $payload = ['assigned_to_user_id' => $firstAssignee->id, 'location' => 'Room 101'];
        $this->actingAs($staff)->post(route('accountable-items.assign', $this->asset), $payload);

        $this->actingAs($staff)->from(route('accountable-items.show', $this->asset))
            ->post(route('accountable-items.assign', $this->asset), ['assigned_to_user_id' => $secondAssignee->id, 'location' => 'Room 102'])
            ->assertSessionHasErrors('assignment');

        $this->assertSame(1, ItemAssignment::count());
        $this->assertSame($firstAssignee->id, $this->asset->fresh()->current_user_id);
        $this->assertSame(1, AuditLog::where('action', 'assigned')->count());
    }

    public function test_transfer_closes_previous_assignment_and_records_both_sides(): void
    {
        $staff = $this->userWithRole('supply_administrator');
        $firstAssignee = User::factory()->create(['name' => 'First Custodian']);
        $nextAssignee = User::factory()->create(['name' => 'Next Custodian']);
        $this->actingAs($staff)->post(route('accountable-items.assign', $this->asset), [
            'assigned_to_user_id' => $firstAssignee->id,
            'location' => 'Room 101',
        ]);

        $this->actingAs($staff)->post(route('accountable-items.transfer', $this->asset), [
            'assigned_to_user_id' => $nextAssignee->id,
            'location' => 'Room 202',
            'reason' => 'Office relocation',
        ])->assertRedirect(route('accountable-items.show', $this->asset));

        $assignments = ItemAssignment::orderBy('id')->get();
        $transfer = ItemTransfer::firstOrFail();
        $this->assertCount(2, $assignments);
        $this->assertSame(ItemAssignment::STATUS_TRANSFERRED, $assignments[0]->status);
        $this->assertNotNull($assignments[0]->released_at);
        $this->assertSame(ItemAssignment::STATUS_ACTIVE, $assignments[1]->status);
        $this->assertSame($assignments[0]->id, $transfer->from_assignment_id);
        $this->assertSame($assignments[1]->id, $transfer->to_assignment_id);
        $this->assertSame('Room 101', $transfer->from_location);
        $this->assertSame('Room 202', $transfer->to_location);
        $this->assertSame('Office relocation', $transfer->reason);
        $this->assertSame($nextAssignee->id, $this->asset->fresh()->current_user_id);
        $this->assertSame('assigned', $this->asset->fresh()->status);
        $this->assertSame(1, AuditLog::where('action', 'transferred')->count());

        $this->actingAs($staff)->get(route('accountable-items.show', $this->asset))
            ->assertOk()->assertSee('Transfer assignment')->assertSee('First Custodian')->assertSee('Room 101');
    }

    public function test_transfer_requires_one_active_assignment_and_does_not_mutate_without_it(): void
    {
        $staff = $this->userWithRole('supply_administrator');
        $assignee = User::factory()->create();

        $this->actingAs($staff)->from(route('accountable-items.show', $this->asset))
            ->post(route('accountable-items.transfer', $this->asset), [
                'assigned_to_user_id' => $assignee->id,
                'location' => 'Room 202',
            ])->assertSessionHasErrors('assignment');

        $this->assertSame(0, ItemAssignment::count());
        $this->assertSame(0, ItemTransfer::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_assignment_requires_a_user_department_or_office(): void
    {
        $this->actingAs($this->userWithRole('supply_administrator'))
            ->from(route('accountable-items.show', $this->asset))
            ->post(route('accountable-items.assign', $this->asset), ['location' => 'Room 102'])
            ->assertSessionHasErrors('assigned_to_user_id');

        $this->assertSame(0, ItemAssignment::count());
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }
}