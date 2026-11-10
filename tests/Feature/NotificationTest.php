<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Notifications\ReadingPlanReminder;

class NotificationTest extends TestCase
{
     use RefreshDatabase;
     /**
     * A basic feature test example.
     */
    public function test_authenticated_user_can_view_notifications(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
    }

    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->get('/notifications');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_mark_notification_as_read(): void
    {
        $user = \App\Models\User::factory()->create();

        // テスト用の通知を作成
        $user->notify(new ReadingPlanReminder());

        $notification = $user->notifications()->first();

        // 通知を既読にする
        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        // 通知一覧へ戻ることを確認
        $response->assertRedirect(route('notifications.index'));

        // データベースで既読になっていることを確認
        $this->assertNotNull($notification->fresh()->read_at);
    }
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = \App\Models\User::factory()->create();
        $otherUser = \App\Models\User::factory()->create();

        // 別のユーザーに通知を作成
        $otherUser->notify(new ReadingPlanReminder());

        $notification = $otherUser->notifications()->first();

        // 自分以外の通知を既読にしようとする
        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        // 他人の通知は見つからない扱いになる
        $response->assertNotFound();

        // 通知が未読のままであることを確認
        $this->assertNull($notification->fresh()->read_at);
    }
}
