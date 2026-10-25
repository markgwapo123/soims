<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\PhysicalInventory;
use App\Models\PhysicalInventoryItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_reports_require_view_reports_permission(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->actingAs($this->userWithRole('program_department'))->get(route('reports.index'))->assertForbidden();
        $this->actingAs($this->userWithRole('daa'))->get(route('reports.index'))->assertOk()->assertSee('Reports');
    }

    public function test_default_inventory_report_can_be_exported_without_date_filters(): void
    {
        $user = $this->userWithRole('supply_administrator');

        $this->actingAs($user)->get(route('reports.export', ['type' => 'inventory_balances']))
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_inventory_quantities_are_whole_numbers_in_table_and_csv(): void
    {
        $user = $this->userWithRole('supply_administrator');
        $item = $this->item();
        Inventory::create([
            'item_id' => $item->id,
            'quantity_on_hand' => 7,
            'quantity_reserved' => 2,
        ]);

        $this->actingAs($user)->get(route('reports.index', ['type' => 'inventory_balances']))
            ->assertOk()
            ->assertSee('Test toner')
            ->assertDontSee('7.000')
            ->assertDontSee('2.000')
            ->assertDontSee('5.000');

        $csv = $this->actingAs($user)
            ->get(route('reports.export', ['type' => 'inventory_balances']))
            ->streamedContent();
        $this->assertStringContainsString('Test toner', $csv);
        $this->assertStringNotContainsString('7.000', $csv);
        $this->assertStringNotContainsString('2.000', $csv);
        $this->assertStringNotContainsString('5.000', $csv);
    }

    public function test_quantity_formatter_preserves_money_and_identifiers(): void
    {
        $this->assertSame('7', \App\Support\QuantityFormatter::reportValue('quantity_on_hand', '7.000'));
        $this->assertSame('0', \App\Support\QuantityFormatter::reportValue('quantity_reserved', '0.000'));
        $this->assertSame('19.50', \App\Support\QuantityFormatter::reportValue('unit_cost', '19.50'));
        $this->assertSame('2100', \App\Support\QuantityFormatter::reportValue('stock_no', '2100'));
        $this->assertSame('-', \App\Support\QuantityFormatter::reportValue('reorder_level', null));
    }

    public function test_stock_movement_report_filters_ledger_dates_and_exports_safe_csv(): void
    {
        $user = $this->userWithRole('supply_administrator');
        $item = $this->item();
        foreach ([
            ['occurred_at' => '2026-10-01 09:00:00', 'reason' => 'Before range', 'quantity' => 5],
            ['occurred_at' => '2026-10-03 09:00:00', 'reason' => '=HYPERLINK("https://example.invalid")', 'quantity' => -2],
        ] as $movement) {
            InventoryTransaction::create($movement + [
                'item_id' => $item->id,
                'type' => 'release',
                'reference_type' => 'RIS',
                'unit_cost' => 10,
            ]);
        }

        $this->actingAs($user)->get(route('reports.index', [
            'type' => 'stock_movements', 'from' => '2026-10-02', 'to' => '2026-10-04',
        ]))->assertOk()->assertSee('HYPERLINK')->assertDontSee('Before range');

        $response = $this->actingAs($user)->get(route('reports.export', ['type' => 'stock_movements', 'from' => '2026-10-02', 'to' => '2026-10-04']));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
        $this->assertStringNotContainsString('Before range', $response->streamedContent());
    }

    public function test_discrepancy_report_includes_only_completed_nonzero_variances(): void
    {
        $user = $this->userWithRole('administrative_officer');
        $completed = $this->physicalInventory($user, PhysicalInventory::STATUS_COMPLETED, 'PI-REPORT-DONE');
        $draft = $this->physicalInventory($user, PhysicalInventory::STATUS_DRAFT, 'PI-REPORT-DRAFT');
        foreach ([[$completed, 'Recorded shortage'], [$draft, 'Draft-only shortage']] as [$document, $description]) {
            PhysicalInventoryItem::create([
                'physical_inventory_id' => $document->id,
                'article' => 'Desk',
                'description' => $description,
                'quantity_per_property_card' => 3,
                'quantity_per_physical_count' => 1,
                'shortage_quantity' => 2,
                'overage_quantity' => 0,
            ]);
        }

        $this->actingAs($user)->get(route('reports.index', ['type' => 'physical_inventory']))
            ->assertOk()->assertSee('Recorded shortage')->assertDontSee('Draft-only shortage');
    }

    private function physicalInventory(User $user, string $status, string $number): PhysicalInventory
    {
        return PhysicalInventory::create([
            'physical_inventory_uuid' => (string) Str::uuid(),
            'inventory_no' => $number,
            'conducted_by' => $user->id,
            'conducted_at' => '2026-10-03 12:00:00',
            'status' => $status,
        ]);
    }

    private function item(): Item
    {
        $category = Category::create(['code' => 'RPT', 'name' => 'Report items', 'is_active' => true]);

        return Item::create([
            'stock_no' => 'RPT-001', 'name' => 'Test toner', 'category_id' => $category->id,
            'classification' => 'consumable', 'unit_of_measure' => 'box', 'is_active' => true,
        ]);
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }
}