<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderRefund extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $detail;

    public function __construct($order, $detail = [])
    {
        $this->order = $order;
        $this->detail = $detail;
    }

    public function build()
    {
        $appName = \App\Models\Setting::first()->app_name ?? 'Teptix';
        $fromEmail = env('MAIL_FROM_ADDRESS', 'noreply@teptix.com');

        return $this->from($fromEmail, $appName)
            ->subject(__('Your Order #' . $this->order->order_id . ' Has Been Refunded'))
            ->view('emails.order_refund', [
                'order' => $this->order,
                'detail' => $this->detail,
                'appName' => $appName,
            ]);
    }
}
