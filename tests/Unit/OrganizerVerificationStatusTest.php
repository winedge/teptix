<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\OrganizerVerificationStatus;
use Carbon\Carbon;
use Tests\TestCase;

class OrganizerVerificationStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_shows_waiting_status_before_three_days_from_registration()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-08 09:00:00', 'Asia/Kolkata'));

        $user = new User(['is_verify' => 0]);
        $user->created_at = Carbon::parse('2026-07-06 10:00:00', 'Asia/Kolkata');

        $status = OrganizerVerificationStatus::status($user);

        $this->assertSame('waiting', $status['state']);
        $this->assertSame('Onboarding In Progress', $status['title']);
        $this->assertStringContainsString('waiting mode', $status['message']);
    }

    public function test_it_shows_contact_admin_status_after_three_days_from_registration()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-09 10:00:00', 'Asia/Kolkata'));

        $user = new User(['is_verify' => 0]);
        $user->created_at = Carbon::parse('2026-07-06 10:00:00', 'Asia/Kolkata');

        $status = OrganizerVerificationStatus::status($user);

        $this->assertSame('pending', $status['state']);
        $this->assertSame('Organizer Verification Pending', $status['title']);
        $this->assertSame(
            'Please contact the admin for verification. Contact the admin after July 9, 2026. You can create events only after your verification is completed successfully.',
            $status['message']
        );
    }

    public function test_verified_organizers_have_no_pending_status()
    {
        $user = new User(['is_verify' => 1]);
        $user->created_at = Carbon::parse('2026-07-06 10:00:00', 'Asia/Kolkata');

        $status = OrganizerVerificationStatus::status($user);

        $this->assertNull($status);
    }
}
