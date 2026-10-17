<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SoimsNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_users_only_see_notifications_sent_to_them_or_their_active_roles(): void
    {
        $recipient = $this->userWithRole('daa');
        $otherMember = $this->userWithRole('daa');
        $outsider = $this->userWithRole('program_department');
        $direct = $this->notification(['user_id' => $recipient->id, 'type' => 'request_approved', 'title' => 'Direct notice']);
        $role = $this->notification(['role_key' => 'daa', 'type' => 'approval_needed', 'title' => 'Role notice']);
        $private = $this->notification(['user_id' => $otherMember->id, 'type' => 'private', 'title' => 'Private notice']);

        $this->actingAs($recipient)->get(route('notifications.index'))->assertOk()
            ->assertSee('Direct notice')->assertSee('Role notice')->assertDontSee('Private notice');
        $this->actingAs($outsider)->get(route('notifications.index'))->assertOk()
            ->assertDontSee('Role notice')->assertDontSee('Direct notice');
        $this->assertNotNull($direct->id);
        $this->assertNotNull($role->id);
        $this->assertNotNull($private->id);
    }

    public function test_role_notification_read_state_is_independent_for_each_user(): void
    {
        $firstUser = $this->userWithRole('daa');
        $secondUser = $this->userWithRole('daa');
        $notification = $this->notification(['role_key' => 'daa', 'type' => 'approval_needed', 'title' => 'Review required']);

        $this->actingAs($firstUser)->post(route('notifications.read', $notification))->assertRedirect();

        $this->actingAs($firstUser)->get(route('notifications.index'))->assertSee('Read')->assertDontSee('Mark read');
        $this->actingAs($secondUser)->get(route('notifications.index'))->assertSee('Unread')->assertSee('Mark read');
        $this->assertDatabaseHas('notification_reads', ['notification_id' => $notification->id, 'user_id' => $firstUser->id]);
        $this->assertDatabaseMissing('notification_reads', ['notification_id' => $notification->id, 'user_id' => $secondUser->id]);
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_only_marks_notifications_visible_to_the_current_user(): void
    {
        $user = $this->userWithRole('administrative_officer');
        $other = $this->userWithRole('program_department');
        $roleNotice = $this->notification(['role_key' => 'administrative_officer', 'type' => 'review', 'title' => 'Role unread']);
        $privateNotice = $this->notification(['user_id' => $other->id, 'type' => 'private', 'title' => 'Other unread']);

        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertDatabaseHas('notification_reads', ['notification_id' => $roleNotice->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('notification_reads', ['notification_id' => $privateNotice->id, 'user_id' => $user->id]);
        $this->assertNull($roleNotice->fresh()->read_at);
    }

    public function test_users_cannot_mark_another_users_notification_read(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $recipient = $this->userWithRole('program_department');
        $other = $this->userWithRole('program_department');
        $notification = $this->notification(['user_id' => $other->id, 'type' => 'private', 'title' => 'Private notice']);

        $this->actingAs($recipient)->post(route('notifications.read', $notification))->assertNotFound();
    }

    private function notification(array $attributes): SoimsNotification
    {
        return SoimsNotification::create($attributes + [
            'type' => 'system',
            'title' => 'Notice',
            'message' => 'Notification message',
        ]);
    }

    private function userWithRole(string $roleKey): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $roleKey)->firstOrFail(), ['assigned_at' => now()]);

        return $user;
    }
}