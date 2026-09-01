<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($content,$detail)
    {
        //
        $this->content = $content;
        $this->detail = $detail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->replaceContent();

        // Get sender email from config (set by controller) or fallback to env
        $fromAddress = config('mail.from.address') ?? env('MAIL_FROM_ADDRESS', 'noreply@example.com');
        $fromName = config('mail.from.name') ?? $this->detail['app_name'];

        return $this->from($fromAddress, $fromName)
            ->subject('Reset Password')->view('admin/mailMessage')
            ->with([
            'content' => $this->content,
        ]);
    }

    public function replaceContent() {
        $data = ["{{user_name}}", "{{password}}","{{app_name}}"];
        $this->content = str_replace($data, $this->detail, $this->content);
    }
}
