<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $readingPlanId,
        public string $bookTitle,
        public string $timing
    ) {
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlanId,
            'book_title' => $this->bookTitle,
            'title' => '読書計画のお知らせ',
            'body' => match ($this->timing) {
                'three_days_before' => "『{$this->bookTitle}』の読書計画の期日まであと3日です。",
                'on_due_date' => "『{$this->bookTitle}』の読書計画は本日が期日です。",
                'three_days_after' => "『{$this->bookTitle}』の読書計画は期日から3日が経過しました。",
                default => "『{$this->bookTitle}』の読書計画の期日をご確認ください。",
            },
            'timing' => $this->timing,
        ];
    }
}
