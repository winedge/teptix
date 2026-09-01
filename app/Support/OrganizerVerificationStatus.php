<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

class OrganizerVerificationStatus
{
    private const WAITING_DAYS = 3;

    public static function contactDate(User $user): ?Carbon
    {
        if (!$user->created_at) {
            return null;
        }

        $createdAt = $user->created_at instanceof Carbon
            ? $user->created_at
            : Carbon::parse($user->created_at);

        return $createdAt->copy()->addDays(self::WAITING_DAYS);
    }

    public static function status(User $user): ?array
    {
        if ((int) $user->is_verify === 1) {
            return null;
        }

        if ((int) $user->is_verify === 2) {
            $reasonText = $user->denied_reason ? ' ' . __('Reason:') . ' ' . $user->denied_reason : '';
            return [
                'state' => 'rejected',
                'title' => __('Organizer Verification Rejected'),
                'message' => __('Your organizer account has been rejected by the admin. You cannot create events.') . $reasonText,
                'contact_date' => null,
            ];
        }

        $contactDate = self::contactDate($user);

        if (
            $contactDate &&
            Carbon::now($contactDate->getTimezone())->startOfDay()->lt($contactDate->copy()->startOfDay())
        ) {
            return [
                'state' => 'waiting',
                'title' => __('Onboarding In Progress'),
                'message' => __('The onboarding process is in progress and your account is currently in waiting mode. You can create events only after your verification is completed successfully.'),
                'contact_date' => $contactDate,
            ];
        }

        $formattedDate = $contactDate ? $contactDate->format('F j, Y') : __('the 3-day waiting period');

        return [
            'state' => 'pending',
            'title' => __('Organizer Verification Pending'),
            'message' => __('Please contact the admin for verification. Contact the admin after :date. You can create events only after your verification is completed successfully.', [
                'date' => $formattedDate,
            ]),
            'contact_date' => $contactDate,
        ];
    }

    public static function blockingMessage(User $user): string
    {
        $status = self::status($user);

        return $status['message'] ?? __('You can create events only after your verification is completed successfully.');
    }
}
