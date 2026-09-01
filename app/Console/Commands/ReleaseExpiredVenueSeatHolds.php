<?php

namespace App\Console\Commands;

use App\Models\EventVenueSeat;
use Illuminate\Console\Command;

class ReleaseExpiredVenueSeatHolds extends Command
{
    protected $signature = 'venue-seats:release-expired-holds';

    protected $description = 'Release expired temporary venue seat holds.';

    public function handle()
    {
        $released = EventVenueSeat::expiredHolds()->update([
            'status' => EventVenueSeat::STATUS_AVAILABLE,
            'hold_token' => null,
            'held_by_session_id' => null,
            'held_by_app_user_id' => null,
            'held_by_guest_user_id' => null,
            'held_at' => null,
            'hold_expires_at' => null,
        ]);

        $this->info("Released {$released} expired venue seat hold(s).");

        return self::SUCCESS;
    }
}
