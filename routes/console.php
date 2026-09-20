<?php

use App\Models\StockReservation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('commerce:release-expired-reservations', function () {
    $released = StockReservation::query()
        ->whereNull('committed_at')
        ->whereNull('released_at')
        ->where('expires_at', '<=', now())
        ->update(['released_at' => now()]);

    $this->info("Released {$released} expired stock reservation(s).");
})->purpose('Release expired unpaid checkout stock reservations');

// Hostinger shared hosting runs the scheduler through cron; workers stop after
// draining the queue so no persistent process is required.
Schedule::command('queue:work database --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('commerce:release-expired-reservations')
    ->everyMinute()
    ->withoutOverlapping();
