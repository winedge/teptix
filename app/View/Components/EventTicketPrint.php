<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Setting;
use App\Models\Ticket;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EventTicketPrint extends Component
{
    public $ticket;
    public $setting;
    public $qrCode;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($ticket)
    {
        $this->ticket=Ticket::with('event')->find($ticket);
        $this->setting=Setting::find(1);
        $this->qrCode =QrCode::size(150)->generate($this->ticket->ticket_number);
       
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.event-ticket-print');
    }
}
