<?php

namespace App\Mail;

use App\Models\OrganizerTax;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaxApproved extends Mailable
{
    use Queueable, SerializesModels;

    public OrganizerTax $tax;

    public function __construct(OrganizerTax $tax)
    {
        $this->tax = $tax;
    }

    public function build()
    {
        return $this->subject('Your Tax Has Been Approved')
                    ->view('emails.tax_approved');
    }
}
