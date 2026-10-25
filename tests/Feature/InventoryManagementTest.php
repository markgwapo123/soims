<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AccountableItem;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Inventory;
use App\Models\InventoryLot;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\CarbonImmutable;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_receiving_updates_balance_and_ledger(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('key', 'supply_administrator')->firstOrFail(), ['assigned_at' => now()]);
        $category = Category::create(['code' => 'PAPER', 'name' => 'Paper', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.inventory.receive.store'), [
            'stock_no' => 'PAPER_A4', 'name' => 'A4 Paper', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'brand' => 'PaperPro',
            'quality' => 'Premium', 'supplier' => 'Office Supply Co.', 'quantity' => 10, 'location' => 'Supply Room', 'lot_no' => 'LOT-1',
            'received_date' => '2026-10-02', 'unit_cost' => 250, 'reason' => 'Initial receipt',
        ])->assertRedirect(route('admin.inventory.index'));

        $item = Item::where('stock_no', 'PAPER_A4')->firstOrFail();
        $this->assertSame('PaperPro', $item->brand);
        $this->assertSame('Premium', $item->quality);
        $this->assertSame('Office Supply Co.', $item->supplier);
        $this->assertSame('10.000', Inventory::where('item_id', $item->id)->firstOrFail()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_transactions', ['item_id' => $item->id, 'type' => 'receipt', 'quantity' => 10]);
        $this->assertDatabaseHas('inventory_lots', ['item_id' => $item->id, 'lot_no' => 'LOT-1', 'quantity_on_hand' => 10]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id, 'action' => 'received', 'module' => 'inventory',
            'record_type' => Inventory::class, 'record_id' => Inventory::where('item_id', $item->id)->value('id'),
            'reason' => 'Initial receipt',
        ]);

        $this->actingAs($admin)->get(route('stock-availability.index'))
            ->assertOk()->assertSee('A4 Paper')->assertSee('10');

        $departmentUser = User::factory()->create();
        $departmentUser->roles()->attach(Role::where('key', 'program_department')->firstOrFail(), ['assigned_at' => now()]);
        $this->actingAs($departmentUser)->get(route('stock-availability.index'))
            ->assertOk()->assertSee('A4 Paper')->assertSee('10');

        $this->actingAs($admin)->get(route('stock-availability.index'))
            ->assertOk()->assertSee('A4 Paper')->assertSee('10');

        $departmentUser = User::factory()->create();
        $departmentUser->roles()->attach(Role::where('key', 'program_department')->firstOrFail(), ['assigned_at' => now()]);
        $this->actingAs($departmentUser)->get(route('stock-availability.index'))
            ->assertOk()->assertSee('A4 Paper')->assertSee('10');
    }

    public function test_receiving_existing_stock_adds_to_balance_without_creating_duplicate(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('key', 'supply_administrator')->firstOrFail(), ['assigned_at' => now()]);
        $category = Category::create(['code' => 'SUPPLY', 'name' => 'Supply', 'is_active' => true]);
        $item = Item::create([
            'stock_no' => 'BOND_2100', 'name' => 'Bond paper', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'is_active' => true,
        ]);
        Inventory::create(['item_id' => $item->id, 'quantity_on_hand' => 4, 'quantity_reserved' => 0]);

        $this->actingAs($admin)->post(route('admin.inventory.receive.store'), [
            'stock_no' => 'BOND_2100', 'name' => 'Bond paper', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream',
            'quantity' => 4, 'received_date' => now()->toDateString(),
        ])->assertRedirect(route('admin.inventory.index'));

        $this->assertSame(1, Item::where('stock_no', 'BOND_2100')->count());
        $this->assertSame('8.000', $item->inventory()->firstOrFail()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_transactions', [
            'item_id' => $item->id, 'type' => 'receipt', 'quantity' => 4,
        ]);
    }

    public function test_inventory_requires_manage_inventory_permission(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', 'program_department')->firstOrFail(), ['assigned_at' => now()]);

        $this->actingAs($user)->get(route('admin.inventory.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.inventory.receive.store'), [])->assertForbidden();
    }

    public function test_department_can_view_request_safe_stock_availability(): void
    {
        $departmentUser = User::factory()->create();
        $departmentUser->roles()->attach(Role::where('key', 'program_department')->firstOrFail(), ['assigned_at' => now()]);
        $category = Category::create(['code' => 'OFFICE', 'name' => 'Office Supplies', 'is_active' => true]);
        $item = Item::create([
            'stock_no' => 'PAPER_A4', 'name' => 'A4 Bond Paper', 'description' => 'Standard office paper',
            'category_id' => $category->id, 'classification' => 'consumable', 'unit_of_measure' => 'ream',
            'default_unit_price' => 100, 'is_active' => true,
        ]);
        Inventory::create([
            'item_id' => $item->id, 'location' => 'Internal Supply Room',
            'quantity_on_hand' => 10, 'quantity_reserved' => 2,
        ]);
        $assetItem = Item::create([
            'stock_no' => 'DESKTOP-01', 'name' => 'Desktop Computer', 'description' => 'Office workstation',
            'category_id' => $category->id, 'classification' => 'accountable', 'unit_of_measure' => 'unit', 'is_active' => true,
        ]);
        AccountableItem::create([
            'item_id' => $assetItem->id, 'property_no' => 'PROPERTY-INTERNAL-01',
            'barcode' => 'BARCODE-INTERNAL-01', 'status' => 'available',
        ]);

        $this->actingAs($departmentUser)->get(route('stock-availability.index'))
            ->assertOk()
            ->assertSee('A4 Bond Paper')
            ->assertSee('Office Supplies')
            ->assertSee('ream')
            ->assertSee('8')
            ->assertSee('Desktop Computer')
            ->assertSee('Available')
            ->assertDontSee('Internal Supply Room')
            ->assertDontSee('PROPERTY-INTERNAL-01')
            ->assertDontSee('BARCODE-INTERNAL-01')
            ->assertDontSee('100.00');
    }

    public function test_stock_view_supports_database_search_filters_and_supply_summary(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('key', 'supply_administrator')->firstOrFail(), ['assigned_at' => now()]);
        $category = Category::create(['code' => 'PAPER', 'name' => 'Office Supplies', 'is_active' => true]);
        $paper = Item::create([
            'stock_no' => 'PAPER_A4', 'name' => 'Bond Paper A4', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'reorder_level' => 5, 'is_active' => true,
        ]);
        $pen = Item::create([
            'stock_no' => 'PEN_BLUE', 'name' => 'Blue Ballpen', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'piece', 'reorder_level' => 5, 'is_active' => true,
        ]);
        $folder = Item::create([
            'stock_no' => 'FOLDER', 'name' => 'Document Folder', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'piece', 'reorder_level' => 5, 'is_active' => true,
        ]);
        $computer = Item::create([
            'stock_no' => 'DESKTOP-01', 'name' => 'Desktop Computer', 'category_id' => $category->id,
            'classification' => 'accountable', 'unit_of_measure' => 'unit', 'is_active' => true,
        ]);
        AccountableItem::create(['item_id' => $computer->id, 'property_no' => 'PROP-001', 'barcode' => 'BAR-001', 'status' => 'available']);
        AccountableItem::create(['item_id' => $computer->id, 'property_no' => 'PROP-002', 'barcode' => 'BAR-002', 'status' => 'assigned']);
        Inventory::create(['item_id' => $paper->id, 'quantity_on_hand' => 10, 'quantity_reserved' => 2]);
        Inventory::create(['item_id' => $pen->id, 'quantity_on_hand' => 3, 'quantity_reserved' => 0]);
        Inventory::create(['item_id' => $folder->id, 'quantity_on_hand' => 5, 'quantity_reserved' => 5]);

        $screen = $this->actingAs($admin)->get(route('stock-availability.index'));
        $screen->assertOk()
            ->assertSee('Total items')
            ->assertSee('Available consumable stock')
            ->assertSee('Bond Paper A4')
            ->assertSee('8')
            ->assertSee('Available accountable assets')
            ->assertSee('Desktop Computer')
            ->assertSee('2 assets')
            ->assertSee('1 assets')
            ->assertSee('Low Stock')
            ->assertSee('Out of Stock');

        $this->get(route('stock-availability.index', ['search' => 'PEN_BLUE', 'availability' => 'low_stock']))
            ->assertOk()->assertSee('Blue Ballpen')->assertDontSee('Bond Paper A4')->assertDontSee('Document Folder');
        $this->get(route('stock-availability.index', ['category_id' => $category->id, 'type' => 'consumable']))
            ->assertOk()->assertSee('Bond Paper A4')->assertSee('Blue Ballpen')->assertSee('Document Folder');
    }

    public function test_stock_view_access_follows_existing_permissions_and_is_read_only(): void
    {
        foreach (['supply_administrator', 'supply_sub_administrator', 'administrative_officer', 'daa', 'program_department'] as $roleKey) {
            $user = User::factory()->create();
            $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);
            $this->actingAs($user)->get(route('stock-availability.index'))->assertOk();
        }

        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized)->get(route('stock-availability.index'))->assertForbidden();
        $this->post('/stock-availability')->assertMethodNotAllowed();
        $this->get('/admin/inventory')->assertForbidden();
    }

    public function test_expiration_report_classifies_valid_near_and_expired_lots(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('key', 'supply_administrator')->firstOrFail(), ['assigned_at' => now()]);
        $category = Category::create(['code' => 'MED', 'name' => 'Medical', 'is_active' => true]);
        $item = Item::create(['stock_no' => 'MED_1', 'name' => 'Test Item', 'category_id' => $category->id, 'classification' => 'consumable', 'unit_of_measure' => 'box', 'is_active' => true]);

        foreach ([
            ['lot_no' => 'VALID', 'expiration_date' => CarbonImmutable::today()->addDays(31)],
            ['lot_no' => 'NEAR', 'expiration_date' => CarbonImmutable::today()->addDays(10)],
            ['lot_no' => 'EXPIRED', 'expiration_date' => CarbonImmutable::yesterday()],
        ] as $lot) {
            InventoryLot::create($lot + ['item_id' => $item->id, 'received_date' => CarbonImmutable::today()->subMonth(), 'quantity_on_hand' => 1, 'is_active' => true]);
        }

        $this->actingAs($admin)->get(route('admin.inventory.expiration.status', 'near_expiration'))
            ->assertOk()->assertSee('NEAR')->assertDontSee('VALID')->assertDontSee('EXPIRED');
        $this->actingAs($admin)->get(route('admin.inventory.expiration.status', 'expired'))
            ->assertOk()->assertSee('EXPIRED')->assertDontSee('NEAR');
    }
}
