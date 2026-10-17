<?php

namespace Tests\Feature;

use App\Models\AccountableItem;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\PhysicalInventory;
use App\Models\PhysicalInventoryItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysicalInventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $departmentUser;
    private Item $consumable;
    private Item $accountableCatalogItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->admin = $this->userWithRole('supply_administrator');
        $this->departmentUser = $this->userWithRole('program_department');
        $category = Category::create(['code' => 'COUNT', 'name' => 'Countable items', 'is_active' => true]);
        $this->consumable = Item::create([
            'stock_no' => 'COUNT-CONS', 'name' => 'Bond Paper', 'description' => 'A4 paper', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'default_unit_price' => 250, 'is_active' => true,
        ]);
        $this->accountableCatalogItem = Item::create([
            'stock_no' => 'COUNT-ASSET', 'name' => 'Projector', 'description' => 'Portable projector', 'category_id' => $category->id,
            'classification' => 'accountable', 'unit_of_measure' => 'unit', 'default_unit_price' => 25000, 'is_active' => true,
        ]);
    }

    public function test_physical_count_records_shortage_overage_and_unlisted_item_without_adjusting_stock(): void
    {
        $inventory = Inventory::create(['item_id' => $this->consumable->id, 'quantity_on_hand' => 10, 'quantity_reserved' => 2]);
        $asset = AccountableItem::create([
            'item_id' => $this->accountableCatalogItem->id,
            'property_no' => 'COUNT-PROP-001',
            'barcode' => 'COUNT-BAR-001',
            'unit_value' => 25000,
            'status' => 'assigned',
            'current_location' => 'Room 5',
        ]);
        $ledgerCount = InventoryTransaction::count();

        $this->actingAs($this->admin)->post(route('physical-inventories.store'), [
            'inventory_no' => 'PI-2026-001',
            'conducted_at' => '2026-10-03T09:30',
            'inventory_ids' => [$inventory->id],
            'accountable_item_ids' => [$asset->id],
        ])->assertRedirect();

        $physicalInventory = PhysicalInventory::firstOrFail();
        $lines = $physicalInventory->items()->get();
        $stockLine = $lines->firstWhere('item_id', $this->consumable->id);
        $assetLine = $lines->firstWhere('accountable_item_id', $asset->id);
        $this->assertSame('10.000', $stockLine->quantity_per_property_card);
        $this->assertSame('1.000', $assetLine->quantity_per_property_card);

        $this->actingAs($this->admin)->post(route('physical-inventories.complete', $physicalInventory), [
            'counts' => [
                $stockLine->id => ['quantity_per_physical_count' => 7, 'remarks' => 'Three reams missing'],
                $assetLine->id => ['quantity_per_physical_count' => 0, 'remarks' => 'Not located'],
            ],
            'found_items' => [[
                'article' => 'Unlisted chairs',
                'description' => 'Stacking chair discovered during count',
                'unit_of_measure' => 'piece',
                'quantity_per_physical_count' => 2,
            ]],
        ])->assertRedirect();

        $physicalInventory->refresh();
        $this->assertSame(PhysicalInventory::STATUS_COMPLETED, $physicalInventory->status);
        $this->assertSame('3.000', $stockLine->fresh()->shortage_quantity);
        $this->assertSame('0.000', $stockLine->fresh()->overage_quantity);
        $this->assertSame('1.000', $assetLine->fresh()->shortage_quantity);
        $foundLine = $physicalInventory->items()->whereNull('item_id')->firstOrFail();
        $this->assertSame('2.000', $foundLine->overage_quantity);
        $this->assertSame('10.000', $inventory->fresh()->quantity_on_hand);
        $this->assertSame('2.000', $inventory->fresh()->quantity_reserved);
        $this->assertSame('2026-10-03 09:30:00', $inventory->fresh()->last_counted_at?->format('Y-m-d H:i:s'));
        $this->assertSame('assigned', $asset->fresh()->status);
        $this->assertSame($ledgerCount, InventoryTransaction::count());
        $this->assertDatabaseHas('audit_logs', ['module' => 'physical_inventory', 'action' => 'completed', 'record_id' => $physicalInventory->id]);
    }

    public function test_cancelling_draft_does_not_change_stock_and_completed_count_cannot_repeat(): void
    {
        $inventory = Inventory::create(['item_id' => $this->consumable->id, 'quantity_on_hand' => 4, 'quantity_reserved' => 0]);
        $this->actingAs($this->admin)->post(route('physical-inventories.store'), [
            'inventory_no' => 'PI-2026-002', 'conducted_at' => '2026-10-03T10:00', 'inventory_ids' => [$inventory->id],
        ]);
        $draft = PhysicalInventory::firstOrFail();
        $line = $draft->items()->firstOrFail();

        $this->actingAs($this->admin)->post(route('physical-inventories.cancel', $draft))->assertRedirect();
        $this->assertSame(PhysicalInventory::STATUS_CANCELLED, $draft->fresh()->status);
        $this->assertSame('4.000', $inventory->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('audit_logs', ['module' => 'physical_inventory', 'action' => 'cancelled', 'record_id' => $draft->id]);

        $this->actingAs($this->admin)->post(route('physical-inventories.complete', $draft), [
            'counts' => [$line->id => ['quantity_per_physical_count' => 4]],
        ])->assertSessionHasErrors('inventory');
    }

    public function test_completion_requires_a_count_for_each_line_and_duplicate_inventory_numbers_are_rejected(): void
    {
        $inventory = Inventory::create(['item_id' => $this->consumable->id, 'quantity_on_hand' => 4, 'quantity_reserved' => 0]);
        $payload = ['inventory_no' => 'PI-2026-DUP', 'conducted_at' => '2026-10-03T11:00', 'inventory_ids' => [$inventory->id]];
        $this->actingAs($this->admin)->post(route('physical-inventories.store'), $payload);
        $draft = PhysicalInventory::firstOrFail();
        $line = $draft->items()->firstOrFail();

        $this->actingAs($this->admin)->post(route('physical-inventories.complete', $draft), ['counts' => []])
            ->assertSessionHasErrors('counts');
        $this->actingAs($this->admin)->from(route('physical-inventories.create'))
            ->post(route('physical-inventories.store'), $payload)->assertSessionHasErrors('inventory_no');
        $this->assertSame(PhysicalInventory::STATUS_DRAFT, $draft->fresh()->status);
        $this->assertSame('0.000', $line->fresh()->shortage_quantity);
    }

    public function test_physical_inventory_requires_manage_inventory_permission(): void
    {
        $this->get(route('physical-inventories.index'))->assertRedirect(route('login'));
        $this->actingAs($this->departmentUser)->get(route('physical-inventories.index'))->assertForbidden();
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }
}