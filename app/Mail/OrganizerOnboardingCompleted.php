<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrganizerOnboardingCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public $organizer;

    public function __construct($organizer)
    {
        $this->organizer = $organizer;
    }

    public function build()
    {
        return $this->subject('Onboarding Details Submitted - ' . config('app.name'))
                    ->view('emails.organizer_onboarding_completed');
    }
}
