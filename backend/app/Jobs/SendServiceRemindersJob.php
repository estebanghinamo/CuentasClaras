<?php

namespace App\Jobs;

use App\Services\Notifications\ReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Recordatorios de servicios por vencer (M-17 §4, M-23: diario 09:00). */
class SendServiceRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(ReminderService $reminders): void
    {
        $reminders->sendServiceReminders();
    }
}
