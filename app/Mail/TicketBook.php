<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Queue\SerializesModels;

class TicketBook extends Mailable
{
    use Queueable, SerializesModels;
    public $content;
    public $detail;
    public $subject;
    public $qrcode;
    public $pdfData;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($content, $detail, $subject, $qrcode, $pdfData = null)
    {
        $this->content = $content;
        $this->detail = $detail;
        $this->subject = $subject;
        $this->qrcode = $qrcode;
        $this->pdfData = $pdfData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->replaceContent();
        $qrcode = QrCode::size(160)->generate($this->qrcode);
        $mail = $this->from($address = env('MAIL_FROM_ADDRESS'), $name = $this->detail['app_name'])
            ->subject($this->subject)->view('admin/mailMessage', compact('qrcode'))
            ->with([
                'content' => $this->content,
                'qrcode' => $this->qrcode,
            ]);

        // Attach PDF if provided
        if ($this->pdfData) {
            $mail->attachData($this->pdfData, 'ticket.pdf', [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    public function replaceContent()
    {
        $data = ["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
        $this->content = str_replace($data, $this->detail, $this->content);
    }
}
