<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReadingPlanRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function createPlan(
        User $user,
        int $days,
        string $status
    ) {
        $book = $user->books()->create([
            'title' => 'リマインダーテスト書籍',
            'author' => 'テスト著者',
            'published_date' => '2026-01-01',
        ]);

        return $user->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => today('Asia/Tokyo')
                ->addDays($days)->toDateString(),
            'status' => $status,
        ]);
    }

    public function test_期日3日前に通知される(): void
    {
        $user = User::factory()->create();

        $plan = $this->createPlan(
            $user, 3, 'in_progress'
        );

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        $this->assertCount(1, $user->notifications);

        $notification = $user->notifications->first();

        $this->assertEquals(
            ReadingPlanReminder::class,
            $notification->type
        );

        $this->assertEquals(
            $plan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertEquals(
            'three_days_before',
            $notification->data['timing']
        );

        $this->assertStringContainsString(
            '期日まであと3日です。',
            $notification->data['body']
        );
    }

    public function test_期日当日に通知される(): void
    {
        $user = User::factory()->create();

        $plan = $this->createPlan(
            $user, 0, 'in_progress'
        );

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        $this->assertCount(1, $user->notifications);

        $notification = $user->notifications->first();

        $this->assertEquals(
            $plan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertEquals(
            'on_due_date',
            $notification->data['timing']
        );

        $this->assertStringContainsString(
            '本日が期日です。',
            $notification->data['body']
        );
    }

    public function test_期日から3日経過すると通知される(): void
    {
        $user = User::factory()->create();

        $plan = $this->createPlan(
            $user, -3, 'expired'
        );

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        $this->assertCount(1, $user->notifications);

        $notification = $user->notifications->first();

        $this->assertEquals(
            $plan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertEquals(
            'three_days_after',
            $notification->data['timing']
        );

        $this->assertStringContainsString(
            '期日から3日が経過しました。',
            $notification->data['body']
        );
    }

    public function test_リマインダー通知は重複送信されない(): void
    {
        $user = User::factory()->create();

        $this->createPlan($user, 3, 'in_progress');
        $this->createPlan($user, 0, 'in_progress');
        $this->createPlan($user, -3, 'expired');

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        $this->assertEquals(
            3,
            $user->notifications()->count()
        );

        // 同じコマンドを再実行
        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        // 通知件数が増えていないことを確認
        $this->assertEquals(
            3,
            $user->notifications()->count()
        );
    }

    public function test_通知対象外の読書計画には通知されない(): void
    {
        $user = User::factory()->create();

        $this->createPlan($user, 7, 'in_progress');
        $this->createPlan($user, 3, 'completed');
        $this->createPlan($user, 0, 'completed');
        $this->createPlan($user, -3, 'completed');
        $this->createPlan($user, -2, 'expired');

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        $this->assertEquals(
            0,
            $user->notifications()->count()
        );
    }
}
