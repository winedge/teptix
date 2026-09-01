<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        // Get primary color from settings
        $setting = Setting::first();
        $primaryColor = $setting->primary_color ?? '#007bff';

        return $this->from($address = env('MAIL_FROM_ADDRESS'), "The Event Palette")->
        subject($this->data['title'])
                    ->view('emails.event_notification')
                    ->with([
                        'title' => $this->data['title'],
                        'description' => $this->data['description'],
                        'image' => $this->data['image'],
                        'primaryColor' => $primaryColor,
                    ]);
    }
}
