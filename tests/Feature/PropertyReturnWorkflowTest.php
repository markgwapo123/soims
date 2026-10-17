<?php

namespace Tests\Feature;

use App\Models\AccountableItem;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\ItemAssignment;
use App\Models\PropertyReturn;
use App\Models\PropertyReturnItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PropertyReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Item $catalogItem;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $category = Category::create(['code' => 'PRS', 'name' => 'PRS equipment', 'is_active' => true]);
        $this->catalogItem = Item::create([
            'stock_no' => 'PRS-LAPTOP', 'name' => 'Laptop', 'category_id' => $category->id,
            'classification' => 'accountable', 'unit_of_measure' => 'unit', 'is_active' => true,
        ]);
        $this->staff = $this->userWithRole('supply_administrator');
    }

    public function test_draft_prs_snapshots_assets_and_does_not_change_them(): void
    {
        $asset = $this->asset('PRS-PROP-001');
        $assignment = $this->assignment($asset);

        $response = $this->actingAs($this->staff)->post(route('property-returns.store'), $this->payload($asset, 'repair'));

        $propertyReturn = PropertyReturn::firstOrFail();
        $response->assertRedirect(route('property-returns.show', $propertyReturn));
        $this->assertSame(PropertyReturn::STATUS_DRAFT, $propertyReturn->status);
        $this->assertSame('PRS-2026-001', $propertyReturn->prs_no);
        $this->assertSame('assigned', $asset->fresh()->status);
        $this->assertNull($assignment->fresh()->released_at);

        $line = PropertyReturnItem::firstOrFail();
        $this->assertSame($asset->property_no, $line->property_no_snapshot);
        $this->assertSame('1.000', $line->quantity);
        $this->assertSame('42500.00', $line->unit_value);
        $this->assertDatabaseHas('audit_logs', ['module' => 'property_returns', 'action' => 'created', 'record_id' => $propertyReturn->id]);
    }

    public function test_completed_prs_applies_official_status_mapping_and_closes_assignment(): void
    {
        $mapping = [
            'repair' => 'FOR_REPAIR',
            'disposal' => 'FOR_DISPOSAL',
            'returned_to_stock' => 'IN_STOCK',
            'other' => 'RETURNED',
        ];

        foreach ($mapping as $purpose => $expectedStatus) {
            $asset = $this->asset('PROP-'.strtoupper($purpose));
            $assignment = $this->assignment($asset);
            $propertyReturn = $this->createDraft($asset, $purpose);

            $this->actingAs($this->staff)->post(route('property-returns.complete', $propertyReturn))->assertRedirect(route('property-returns.show', $propertyReturn));

            $this->assertSame(PropertyReturn::STATUS_COMPLETED, $propertyReturn->fresh()->status);
            $this->assertSame($expectedStatus, $asset->fresh()->status);
            $this->assertNull($asset->fresh()->current_user_id);
            $this->assertSame('Supply Office', $asset->fresh()->current_location);
            $this->assertNotNull($assignment->fresh()->released_at);
            $this->assertSame(ItemAssignment::STATUS_RETURNED, $assignment->fresh()->status);
        }

        $this->assertSame(4, AuditLog::where('action', 'returned')->count());
    }

    public function test_other_purpose_is_returned_for_evaluation_not_put_in_stock(): void
    {
        $asset = $this->asset('PRS-OTHER-001');
        $propertyReturn = $this->createDraft($asset, 'other');

        $this->actingAs($this->staff)->post(route('property-returns.complete', $propertyReturn));

        $this->assertSame('RETURNED', $asset->fresh()->status);
        $this->assertNotSame('IN_STOCK', $asset->fresh()->status);
        $this->actingAs($this->staff)->get(route('property-returns.show', $propertyReturn))
            ->assertOk()->assertSee('not automatically placed in stock');
    }

    public function test_cancelling_a_draft_keeps_asset_and_assignment_unchanged(): void
    {
        $asset = $this->asset('PRS-CANCEL-001');
        $assignment = $this->assignment($asset);
        $propertyReturn = $this->createDraft($asset, 'disposal');

        $this->actingAs($this->staff)->post(route('property-returns.cancel', $propertyReturn))->assertRedirect(route('property-returns.show', $propertyReturn));

        $this->assertSame(PropertyReturn::STATUS_CANCELLED, $propertyReturn->fresh()->status);
        $this->assertSame('assigned', $asset->fresh()->status);
        $this->assertNull($assignment->fresh()->released_at);
        $this->assertDatabaseHas('audit_logs', ['module' => 'property_returns', 'action' => 'cancelled', 'record_id' => $propertyReturn->id]);
    }

    public function test_completion_is_atomic_when_any_asset_has_multiple_active_assignments(): void
    {
        $firstAsset = $this->asset('PRS-ATOMIC-001');
        $secondAsset = $this->asset('PRS-ATOMIC-002');
        $firstAssignment = $this->assignment($firstAsset);
        $secondAssignment = $this->assignment($secondAsset);
        $this->assignment($secondAsset);
        $propertyReturn = PropertyReturn::create([
            'property_return_uuid' => (string) Str::uuid(),
            'prs_no' => 'PRS-ATOMIC-001',
            'office_name' => 'Supply Office',
            'purpose' => 'repair',
            'returned_by' => $this->staff->id,
            'received_at' => now(),
            'status' => PropertyReturn::STATUS_DRAFT,
        ]);
        foreach ([$firstAsset, $secondAsset] as $asset) {
            PropertyReturnItem::create([
                'property_return_id' => $propertyReturn->id,
                'accountable_item_id' => $asset->id,
                'property_no_snapshot' => $asset->property_no,
                'description_snapshot' => 'Laptop',
                'quantity' => 1,
                'unit_value' => $asset->unit_value,
            ]);
        }
        $auditCount = AuditLog::count();

        $this->actingAs($this->staff)->from(route('property-returns.show', $propertyReturn))
            ->post(route('property-returns.complete', $propertyReturn))->assertSessionHasErrors('prs');

        $this->assertSame(PropertyReturn::STATUS_DRAFT, $propertyReturn->fresh()->status);
        $this->assertSame('assigned', $firstAsset->fresh()->status);
        $this->assertSame('assigned', $secondAsset->fresh()->status);
        $this->assertNull($firstAssignment->fresh()->released_at);
        $this->assertNull($secondAssignment->fresh()->released_at);
        $this->assertSame($auditCount, AuditLog::count());
    }

    public function test_completed_or_cancelled_prs_cannot_be_transitioned_again(): void
    {
        $asset = $this->asset('PRS-TRANSITION-001');
        $completed = $this->createDraft($asset, 'repair');
        $cancelled = $this->createDraft($this->asset('PRS-TRANSITION-002'), 'other');
        $this->actingAs($this->staff)->post(route('property-returns.complete', $completed));
        $this->actingAs($this->staff)->post(route('property-returns.cancel', $cancelled));

        $this->actingAs($this->staff)->from(route('property-returns.show', $completed))
            ->post(route('property-returns.complete', $completed))->assertSessionHasErrors('prs');
        $this->actingAs($this->staff)->from(route('property-returns.show', $cancelled))
            ->post(route('property-returns.complete', $cancelled))->assertSessionHasErrors('prs');

        $this->assertSame(2, PropertyReturn::count());
    }

    public function test_prs_routes_require_accountable_item_permission(): void
    {
        $this->get(route('property-returns.index'))->assertRedirect(route('login'));
        $asset = $this->asset('PRS-AUTH-001');
        $propertyReturn = $this->createDraft($asset, 'repair');

        $this->actingAs($this->userWithRole('program_department'))->get(route('property-returns.index'))->assertForbidden();
        $this->actingAs($this->userWithRole('program_department'))->post(route('property-returns.complete', $propertyReturn))->assertForbidden();
    }

    public function test_invalid_purpose_and_duplicate_assets_are_rejected(): void
    {
        $asset = $this->asset('PRS-VALIDATION-001');
        $payload = $this->payload($asset, 'stock');
        $payload['items'][] = $asset->id;

        $this->actingAs($this->staff)->from(route('property-returns.create'))
            ->post(route('property-returns.store'), $payload)
            ->assertSessionHasErrors(['purpose', 'items.1']);

        $this->assertSame(0, PropertyReturn::count());
        $this->assertSame(0, AuditLog::count());
    }

    private function createDraft(AccountableItem $asset, string $purpose): PropertyReturn
    {
        $this->actingAs($this->staff)->post(route('property-returns.store'), $this->payload($asset, $purpose));

        return PropertyReturn::latest('id')->firstOrFail();
    }

    private function payload(AccountableItem $asset, string $purpose): array
    {
        return [
            'prs_no' => 'PRS-2026-'.str_pad((string) (PropertyReturn::count() + 1), 3, '0', STR_PAD_LEFT),
            'office_name' => 'Supply Office',
            'purpose' => $purpose,
            'returned_to' => 'Supply Office',
            'items' => [$asset->id],
        ];
    }

    private function asset(string $propertyNo): AccountableItem
    {
        return AccountableItem::create([
            'item_id' => $this->catalogItem->id,
            'property_no' => $propertyNo,
            'barcode' => 'BAR-'.Str::upper(Str::random(12)),
            'unit_value' => 42500,
            'status' => 'assigned',
            'current_location' => 'Room 102',
            'current_user_id' => User::factory()->create()->id,
        ]);
    }

    private function assignment(AccountableItem $asset): ItemAssignment
    {
        return ItemAssignment::create([
            'accountable_item_id' => $asset->id,
            'assigned_to_user_id' => $asset->current_user_id,
            'location' => $asset->current_location,
            'assigned_by' => $this->staff->id,
            'assigned_at' => now()->subDay(),
            'status' => ItemAssignment::STATUS_ACTIVE,
        ]);
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }
}