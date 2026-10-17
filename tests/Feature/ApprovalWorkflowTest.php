<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Category;
use App\Models\Item;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
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
            'classification' => 'consumable', 'unit_of_measure' => 'ream', 'is_active' => true,
        ]);
    }

    public function test_reviewers_only_see_requests_of_their_type(): void
    {
        $academic = $this->makeRequest('academic');
        $nonAcademic = $this->makeRequest('non_academic');

        $this->actingAs($this->userWithRole('daa'))->get(route('approvals.index'))
            ->assertOk()->assertSee($academic->request_no)->assertDontSee($nonAcademic->request_no);

        $this->actingAs($this->userWithRole('administrative_officer'))->get(route('approvals.index'))
            ->assertOk()->assertSee($nonAcademic->request_no)->assertDontSee($academic->request_no);
    }

    public function test_daa_confirms_academic_request_and_requester_is_notified(): void
    {
        $request = $this->makeRequest('academic', 10);
        $daa = $this->userWithRole('daa');

        $this->actingAs($daa)->post(route('approvals.decide', $request), ['decision' => 'approve', 'remarks' => 'OK'])
            ->assertRedirect(route('approvals.index'));

        $request->refresh();
        $this->assertSame('confirmed', $request->status);
        $this->assertNotNull($request->confirmed_at);
        $this->assertSame('10.000', $request->items()->firstOrFail()->confirmed_quantity);
        $this->assertSame('10.000', $request->items()->firstOrFail()->requested_quantity);
        $this->assertDatabaseHas('request_approvals', ['supply_request_id' => $request->id, 'approver_id' => $daa->id, 'decision' => 'approved', 'comments' => 'OK']);
        $this->assertDatabaseHas('notifications', ['user_id' => $request->requestor_id, 'type' => 'request_approved', 'related_id' => $request->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $daa->id, 'action' => 'approved', 'module' => 'requests',
            'record_type' => SupplyRequest::class, 'record_id' => $request->id, 'reason' => 'OK',
        ]);
    }

    public function test_reviewer_cannot_decide_a_request_of_the_other_type(): void
    {
        $nonAcademic = $this->makeRequest('non_academic');

        $this->actingAs($this->userWithRole('daa'))->post(route('approvals.decide', $nonAcademic), ['decision' => 'approve'])
            ->assertForbidden();
        $this->actingAs($this->userWithRole('daa'))->get(route('approvals.show', $nonAcademic))->assertForbidden();

        $this->assertSame('submitted', $nonAcademic->fresh()->status);
    }

    public function test_denial_requires_remarks_and_is_recorded(): void
    {
        $request = $this->makeRequest('non_academic');
        $officer = $this->userWithRole('administrative_officer');

        $this->actingAs($officer)->from(route('approvals.show', $request))
            ->post(route('approvals.decide', $request), ['decision' => 'deny'])
            ->assertSessionHasErrors('remarks');
        $this->assertSame('submitted', $request->fresh()->status);

        $this->actingAs($officer)->post(route('approvals.decide', $request), ['decision' => 'deny', 'remarks' => 'Not in budget']);

        $request->refresh();
        $this->assertSame('denied', $request->status);
        $this->assertSame('Not in budget', $request->rejection_reason);
        $this->assertNull($request->items()->firstOrFail()->confirmed_quantity);
        $this->assertDatabaseHas('notifications', ['user_id' => $request->requestor_id, 'type' => 'request_denied']);
    }

    public function test_a_request_cannot_be_decided_twice(): void
    {
        $request = $this->makeRequest('academic');
        $daa = $this->userWithRole('daa');

        $this->actingAs($daa)->post(route('approvals.decide', $request), ['decision' => 'approve']);
        $this->actingAs($daa)->from(route('approvals.show', $request))
            ->post(route('approvals.decide', $request), ['decision' => 'deny', 'remarks' => 'Changed mind'])
            ->assertSessionHasErrors('decision');

        $this->assertSame('confirmed', $request->fresh()->status);
        $this->assertDatabaseCount('request_approvals', 1);
    }

    public function test_requesters_and_unprivileged_roles_cannot_open_approvals(): void
    {
        $this->actingAs($this->userWithRole('program_department'))->get(route('approvals.index'))->assertForbidden();
        $this->actingAs($this->userWithRole('supply_sub_administrator'))->get(route('approvals.index'))->assertForbidden();
    }

    public function test_requester_sees_the_decision_remarks(): void
    {
        $request = $this->makeRequest('academic');
        $this->actingAs($this->userWithRole('daa'))->post(route('approvals.decide', $request), ['decision' => 'deny', 'remarks' => 'Use existing stock']);

        $this->actingAs($request->requestor)->get(route('requests.show', $request))
            ->assertOk()->assertSee('Use existing stock');
    }

    public function test_supply_administrator_can_review_both_types(): void
    {
        $academic = $this->makeRequest('academic');
        $nonAcademic = $this->makeRequest('non_academic');

        $this->actingAs($this->userWithRole('supply_administrator'))->get(route('approvals.index'))
            ->assertOk()->assertSee($academic->request_no)->assertSee($nonAcademic->request_no);
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }

    private function makeRequest(string $type, int $quantity = 5): SupplyRequest
    {
        $department = Department::create(['code' => strtoupper($type).'_'.uniqid(), 'name' => ucfirst($type).' Dept', 'type' => $type, 'is_active' => true]);
        $requestor = $this->userWithRole('program_department');

        $request = SupplyRequest::create([
            'request_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'request_no' => 'REQ-TEST-'.uniqid(),
            'requestor_id' => $requestor->id, 'department_id' => $department->id,
            'request_type' => $type, 'purpose' => 'Test', 'status' => 'submitted', 'submitted_at' => now(),
        ]);
        $request->items()->create(['item_id' => $this->item->id, 'requested_quantity' => $quantity]);

        return $request;
    }
}
