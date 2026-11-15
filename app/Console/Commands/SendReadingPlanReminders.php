<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;

class SendReadingPlanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-reading-plan-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期日に応じたリマインダー通知を送信する';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = today('Asia/Tokyo');

        $threeDaysLater = $today->copy()->addDays(3);
        $threeDaysAgo = $today->copy()->subDays(3);

        $plansThreeDaysBefore = ReadingPlan::where('status', 'in_progress')
             ->whereDate('target_date', $threeDaysLater)
             ->with(['user', 'book'])
             ->get();

        $plansOnDueDate = ReadingPlan::where('status', 'in_progress')
             ->whereDate('target_date', $today)
             ->with(['user', 'book'])
             ->get();

        $plansThreeDaysAfter = ReadingPlan::where('status', 'expired')
             ->whereDate('target_date', $threeDaysAgo)
             ->with(['user', 'book'])
             ->get();

        foreach ($plansThreeDaysBefore as $plan) {
            $alreadyNotified = $plan->user->notifications()
                ->where('type', ReadingPlanReminder::class)
                ->where('data->reading_plan_id', $plan->id)
                ->where('data->timing', 'three_days_before')
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $plan->user->notify(new ReadingPlanReminder(
                $plan->id,
                $plan->book->title,
                'three_days_before'
            ));
        }

        foreach ($plansOnDueDate as $plan) {
            $alreadyNotified = $plan->user->notifications()
                ->where('type', ReadingPlanReminder::class)
                ->where('data->reading_plan_id', $plan->id)
                ->where('data->timing', 'on_due_date')
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $plan->user->notify(new ReadingPlanReminder(
                $plan->id,
                $plan->book->title,
                'on_due_date'
            ));
        }

        foreach ($plansThreeDaysAfter as $plan) {
            $alreadyNotified = $plan->user->notifications()
                ->where('type', ReadingPlanReminder::class)
                ->where('data->reading_plan_id', $plan->id)
                ->where('data->timing', 'three_days_after')
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $plan->user->notify(new ReadingPlanReminder(
                $plan->id,
                $plan->book->title,
                'three_days_after'
            ));
        }
    }
}
