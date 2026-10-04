<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PlatformAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkspaceAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(?string $role = null): User
    {
        $user = User::factory()->create();
        if ($role) {
            $user->assignRole(Role::findOrCreate($role));
        }

        return $user;
    }

    private function alert(string $category = 'platform', string $url = '/mi-panel'): PlatformAlertNotification
    {
        return new PlatformAlertNotification(category: $category, title: 'Aviso', body: 'Detalle del aviso', url: $url);
    }

    public function test_students_can_only_switch_to_the_learning_space(): void
    {
        $student = $this->userWithRole('Student');

        $this->actingAs($student)->post(route('workspace.switch'), ['space' => 'learning'])->assertRedirect(route('adaptive.dashboard'));
        $this->actingAs($student)->post(route('workspace.switch'), ['space' => 'mentor'])->assertForbidden();
        $this->actingAs($student)->post(route('workspace.switch'), ['space' => 'admin'])->assertForbidden();
        $this->actingAs($student)->post(route('workspace.switch'), ['space' => 'otro'])->assertSessionHasErrors('space');
    }

    public function test_dashboard_respects_the_active_space_and_defaults_by_role(): void
    {
        $admin = $this->userWithRole('Admin');
        $instructor = $this->userWithRole('Instructor');

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($instructor)->get(route('dashboard'))->assertRedirect(route('mentor.dashboard'));

        $this->actingAs($admin)->post(route('workspace.switch'), ['space' => 'learning'])->assertSessionHas('active_space', 'learning');
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('adaptive.dashboard'));

        $this->actingAs($admin)->post(route('workspace.switch'), ['space' => 'mentor'])->assertRedirect(route('mentor.dashboard'));
    }

    public function test_preferences_decide_the_delivery_channels(): void
    {
        $user = $this->userWithRole();
        NotificationPreference::forUser($user)->update(['in_app_platform' => false, 'email_platform' => true, 'in_app_mentorship' => true, 'email_mentorship' => false]);

        $this->assertSame(['mail'], $this->alert('platform')->via($user));
        $this->assertSame(['database'], $this->alert('mentorship')->via($user));
    }

    public function test_first_notification_of_a_user_without_preferences_is_delivered(): void
    {
        $user = $this->userWithRole();
        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $user->id]);

        $this->assertSame(['database', 'mail'], $this->alert()->via($user));
    }

    public function test_user_can_update_preferences_and_unchecked_options_are_saved_as_disabled(): void
    {
        $user = $this->userWithRole();

        $this->actingAs($user)->patch(route('notifications.preferences.update'), ['in_app_messages' => '1', 'email_platform' => '1'])->assertSessionHas('success');

        $preferences = NotificationPreference::forUser($user)->fresh();
        $this->assertTrue($preferences->in_app_messages);
        $this->assertTrue($preferences->email_platform);
        $this->assertFalse($preferences->email_messages);
        $this->assertFalse($preferences->in_app_mentorship);
    }

    public function test_marking_a_notification_as_read_is_limited_to_its_owner(): void
    {
        $owner = $this->userWithRole();
        $other = $this->userWithRole();
        NotificationPreference::forUser($owner)->update(['email_platform' => false]);
        $owner->notify($this->alert('platform', '/retos'));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($other)->post(route('notifications.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($owner)->post(route('notifications.read', $notification->id))->assertRedirect('/retos');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_clears_the_unread_list(): void
    {
        $user = $this->userWithRole();
        NotificationPreference::forUser($user)->update(['email_platform' => false]);
        $user->notify($this->alert());
        $user->notify($this->alert());

        $this->actingAs($user)->post(route('notifications.read-all'))->assertSessionHas('success');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
