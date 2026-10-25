<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\InventoryLot;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\Release;
use App\Models\Ris;
use App\Models\Role;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RisIssuanceTest extends TestCase
{
    use RefreshDatabase;

    private Item $paper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $category = Category::create(['code' => 'SUP', 'name' => 'Supplies', 'is_active' => true]);
        $this->paper = Item::create([
            'stock_no' => 'PAPER_A4', 'name' => 'A4 Paper', 'category_id' => $category->id, 'classification' => 'consumable',
            'unit_of_measure' => 'ream', 'default_unit_price' => 250, 'is_active' => true,
        ]);
    }

    public function test_full_availability_prepares_a_ris_without_touching_stock(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);
        $issuer = $this->staff();

        $this->actingAs($issuer)->post(route('ris.store', $request), ['fund_cluster' => '101'])->assertRedirect();

        $ris = Ris::firstOrFail();
        $this->assertMatchesRegularExpression('/^RIS-\d{4}-\d{6}$/', $ris->ris_no);
        $this->assertSame($issuer->id, $ris->issued_by);
        $this->assertSame($request->requestor_id, $ris->requested_by);
        $this->assertSame('prepared', $ris->status);

        $line = $ris->items()->firstOrFail();
        $this->assertSame('5.000', $line->requested_quantity);
        $this->assertSame('10.000', $line->available_quantity);
        $this->assertSame('5.000', $line->issue_quantity);
        $this->assertSame('0.000', $line->not_available_quantity);
        $this->assertSame('1250.00', $line->amount);
        $this->assertTrue($line->stock_available);

        $this->assertSame('processing', $request->fresh()->status);
        $this->assertSame('10.000', Inventory::firstOrFail()->quantity_on_hand);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $issuer->id, 'action' => 'prepared', 'module' => 'ris',
            'record_type' => Ris::class, 'record_id' => $ris->id,
        ]);
    }

    public function test_partial_availability_records_the_shortfall_and_notifies(): void
    {
        $this->stock(3);
        $request = $this->confirmedRequest(10);

        $this->actingAs($this->staff())->post(route('ris.store', $request))->assertRedirect();

        $line = Ris::firstOrFail()->items()->firstOrFail();
        $this->assertSame('10.000', $line->requested_quantity);
        $this->assertSame('3.000', $line->available_quantity);
        $this->assertSame('3.000', $line->issue_quantity);
        $this->assertSame('7.000', $line->not_available_quantity);
        $this->assertFalse($line->stock_available);
        $this->assertSame('Partial', $line->availability());
        $this->assertDatabaseHas('notifications', ['user_id' => $request->requestor_id, 'type' => 'partial_availability']);
        $this->assertSame('10.000', $request->items()->firstOrFail()->requested_quantity);
    }

    public function test_issue_quantity_can_be_set_lower_than_available(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);
        $lineId = $request->items()->firstOrFail()->id;

        $this->actingAs($this->staff())->post(route('ris.store', $request), ['issue' => [$lineId => 2]]);

        $line = Ris::firstOrFail()->items()->firstOrFail();
        $this->assertSame('2.000', $line->issue_quantity);
        $this->assertSame('3.000', $line->not_available_quantity);
    }

    public function test_supply_and_department_stock_views_stay_in_sync_after_inventory_movements(): void
    {
        $staff = $this->staff();
        $departmentUser = $this->userWithRole('program_department');
        $assertStockViews = function (string $available, string $status = 'Available') use ($staff, $departmentUser): void {
            $this->actingAs($staff)->get(route('dashboard'))
                ->assertOk()->assertSee($this->paper->name)->assertSee($available);
            $this->actingAs($departmentUser)->get(route('stock-availability.index'))
                ->assertOk()->assertSee($this->paper->name)->assertSee($available)->assertSee($status);
        };

        $this->actingAs($staff)->post(route('admin.inventory.receive.store'), [
            'item_id' => $this->paper->id, 'quantity' => 100, 'location' => 'Supply Room',
            'lot_no' => 'LOT-INITIAL', 'received_date' => now()->toDateString(), 'unit_cost' => 250,
        ])->assertRedirect(route('admin.inventory.index'));
        $assertStockViews('100');

        $request = $this->confirmedRequest(20);
        $this->actingAs($staff)->post(route('ris.store', $request))->assertRedirect();
        $ris = Ris::where('supply_request_id', $request->id)->firstOrFail();
        $this->actingAs($staff)->post(route('ris.release', $ris))->assertRedirect();
        $assertStockViews('80');

        $request = $this->confirmedRequest(80);
        $this->actingAs($staff)->post(route('ris.store', $request))->assertRedirect();
        $ris = Ris::where('supply_request_id', $request->id)->firstOrFail();
        $this->actingAs($staff)->post(route('ris.release', $ris))->assertRedirect();
        $assertStockViews('0', 'Out of Stock');

        $this->actingAs($staff)->post(route('admin.inventory.receive.store'), [
            'item_id' => $this->paper->id, 'quantity' => 50, 'location' => 'Supply Room',
            'lot_no' => 'LOT-REFILL', 'received_date' => now()->toDateString(), 'unit_cost' => 250,
        ])->assertRedirect(route('admin.inventory.index'));
        $assertStockViews('50');
    }

    public function test_issue_quantity_cannot_exceed_available_stock(): void
    {
        $this->stock(3);
        $request = $this->confirmedRequest(10);
        $lineId = $request->items()->firstOrFail()->id;

        $this->actingAs($this->staff())->from(route('ris.create', $request))
            ->post(route('ris.store', $request), ['issue' => [$lineId => 5]])
            ->assertSessionHasErrors('issue');

        $this->assertSame(0, Ris::count());
        $this->assertSame('confirmed', $request->fresh()->status);
    }

    public function test_a_ris_is_not_prepared_when_nothing_is_in_stock(): void
    {
        $request = $this->confirmedRequest(5);

        $this->actingAs($this->staff())->from(route('ris.create', $request))
            ->post(route('ris.store', $request))->assertSessionHasErrors('ris');

        $this->assertSame(0, Ris::count());
    }

    public function test_only_confirmed_requests_get_one_ris(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('ris.store', $request));
        $this->actingAs($staff)->get(route('ris.create', $request))->assertNotFound();
        $this->actingAs($staff)->post(route('ris.store', $request))->assertSessionHasErrors('ris');

        $this->assertSame(1, Ris::count());
    }

    public function test_confirmed_item_change_is_used_for_the_ris(): void
    {
        $pencil = Item::create([
            'stock_no' => 'PENCIL', 'name' => 'Pencil', 'category_id' => $this->paper->category_id, 'classification' => 'consumable',
            'unit_of_measure' => 'pc', 'default_unit_price' => 5, 'is_active' => true,
        ]);
        Inventory::create(['item_id' => $pencil->id, 'quantity_on_hand' => 20, 'quantity_reserved' => 0]);
        $request = $this->confirmedRequest(4);
        $request->items()->firstOrFail()->update(['confirmed_item_id' => $pencil->id]);

        $this->actingAs($this->staff())->post(route('ris.store', $request));

        $line = Ris::firstOrFail()->items()->firstOrFail();
        $this->assertSame($pencil->id, $line->item_id);
        $this->assertSame('PENCIL', $line->stock_no_snapshot);
        $this->assertSame('20.00', $line->amount);
    }

    public function test_a_confirmed_request_cannot_be_modified_after_its_ris(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);
        $this->actingAs($this->staff())->post(route('ris.store', $request));

        $this->actingAs($this->staff())->get(route('modifications.edit', $request))->assertForbidden();
    }

    public function test_ris_pages_require_issue_supplies_permission(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);

        foreach (['daa', 'administrative_officer', 'program_department'] as $role) {
            $this->actingAs($this->userWithRole($role))->get(route('ris.index'))->assertForbidden();
        }

        $this->actingAs($this->staff())->get(route('ris.index'))->assertOk()->assertSee($request->request_no);
    }

    public function test_ris_history_and_document_are_viewable(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('ris.store', $request));
        $ris = Ris::firstOrFail();

        $this->actingAs($staff)->get(route('ris.index'))->assertOk()->assertSee($ris->ris_no);
        $this->actingAs($staff)->get(route('ris.show', $ris))->assertOk()->assertSee('PAPER_A4')->assertSee('Full')->assertSee('Release supplies');
    }

    public function test_partial_release_consumes_fefo_lots_then_untracked_stock_and_records_ledger(): void
    {
        $this->stock(10);
        $first = $this->lot(2, now()->addDays(10)->toDateString());
        $second = $this->lot(4, now()->addDays(20)->toDateString());
        $request = $this->confirmedRequest(10);
        $requestLine = $request->items()->firstOrFail();
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('ris.store', $request), ['issue' => [$requestLine->id => 8]]);
        $ris = Ris::firstOrFail();

        $this->actingAs($staff)->post(route('ris.release', $ris), ['notes' => 'Issued to department'])->assertRedirect(route('ris.show', $ris));

        $this->assertSame('released', $ris->fresh()->status);
        $this->assertSame('completed', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->completed_at);
        $this->assertSame('8.000', $requestLine->fresh()->issued_quantity);
        $this->assertSame('2.000', $requestLine->fresh()->unavailable_quantity);
        $this->assertSame('2.000', Inventory::firstOrFail()->quantity_on_hand);
        $this->assertSame('0.000', $first->fresh()->quantity_on_hand);
        $this->assertSame('0.000', $second->fresh()->quantity_on_hand);

        $release = Release::firstOrFail();
        $this->assertSame('Issued to department', $release->notes);
        $movements = InventoryTransaction::where('reference_id', $release->id)->orderBy('id')->get();
        $this->assertSame([-2.0, -4.0, -2.0], $movements->pluck('quantity')->map(fn ($quantity) => (float) $quantity)->all());
        $this->assertSame([$first->id, $second->id, null], $movements->pluck('inventory_lot_id')->all());
        $this->assertSame(['release'], $movements->pluck('type')->unique()->all());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id, 'action' => 'released', 'module' => 'ris',
            'record_type' => Ris::class, 'record_id' => $ris->id, 'reason' => 'Issued to department',
        ]);

        $this->actingAs($staff)->get(route('ris.show', $ris))->assertOk()->assertSee('Released by:');
    }

    public function test_release_rejects_changed_stock_without_partial_mutation(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('ris.store', $request));
        $ris = Ris::firstOrFail();
        Inventory::firstOrFail()->update(['quantity_on_hand' => 2]);

        $this->actingAs($staff)->from(route('ris.show', $ris))->post(route('ris.release', $ris))->assertSessionHasErrors('ris');

        $this->assertSame('prepared', $ris->fresh()->status);
        $this->assertSame('processing', $request->fresh()->status);
        $this->assertSame('2.000', Inventory::firstOrFail()->quantity_on_hand);
        $this->assertDatabaseCount('releases', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_released_ris_cannot_be_released_again(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('ris.store', $request));
        $ris = Ris::firstOrFail();
        $this->actingAs($staff)->post(route('ris.release', $ris))->assertRedirect();

        $this->actingAs($staff)->from(route('ris.show', $ris))->post(route('ris.release', $ris))->assertSessionHasErrors('ris');

        $this->assertDatabaseCount('releases', 1);
        $this->assertSame('5.000', Inventory::firstOrFail()->quantity_on_hand);
    }

    public function test_users_without_issue_permission_cannot_release_a_ris(): void
    {
        $this->stock(10);
        $request = $this->confirmedRequest(5);
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('ris.store', $request));
        $ris = Ris::firstOrFail();

        $this->actingAs($this->userWithRole('program_department'))->post(route('ris.release', $ris))->assertForbidden();

        $this->assertSame('prepared', $ris->fresh()->status);
        $this->assertDatabaseCount('releases', 0);
    }

    private function stock(int $quantity): void
    {
        Inventory::create(['item_id' => $this->paper->id, 'quantity_on_hand' => $quantity, 'quantity_reserved' => 0]);
    }

    private function lot(int $quantity, string $expirationDate): InventoryLot
    {
        return InventoryLot::create([
            'item_id' => $this->paper->id,
            'lot_no' => 'LOT-'.uniqid(),
            'received_date' => now()->subDays(30)->toDateString(),
            'expiration_date' => $expirationDate,
            'quantity_on_hand' => $quantity,
            'is_active' => true,
        ]);
    }

    private function staff(): User
    {
        return $this->userWithRole('supply_sub_administrator');
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }

    private function confirmedRequest(int $quantity): SupplyRequest
    {
        $department = Department::create(['code' => 'D_'.uniqid(), 'name' => 'Academic Dept', 'type' => 'academic', 'is_active' => true]);

        $request = SupplyRequest::create([
            'request_uuid' => (string) Str::uuid(), 'request_no' => 'REQ-TEST-'.uniqid(),
            'requestor_id' => $this->userWithRole('program_department')->id, 'department_id' => $department->id,
            'request_type' => 'academic', 'purpose' => 'Test', 'status' => 'confirmed',
            'submitted_at' => now(), 'confirmed_at' => now(),
        ]);
        $request->items()->create([
            'item_id' => $this->paper->id, 'requested_quantity' => $quantity, 'confirmed_quantity' => $quantity, 'unit_price_snapshot' => 250,
        ]);

        return $request;
    }
}
