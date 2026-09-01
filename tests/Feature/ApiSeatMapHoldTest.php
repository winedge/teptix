<?php

namespace Tests\Feature;

use App\Http\Controllers\ApiController;
use App\Models\EventVenueRow;
use App\Models\EventVenueSeat;
use App\Models\EventVenueSection;
use ReflectionMethod;
use Tests\TestCase;

class ApiSeatMapHoldTest extends TestCase
{
    public function test_seat_matches_selected_ticket_when_section_or_row_is_assigned(): void
    {
        $controller = new ApiController();
        $seat = new EventVenueSeat(['id' => 497, 'ticket_id' => null]);
        $seat->setRelation('section', new EventVenueSection(['ticket_id' => 145]));
        $seat->setRelation('row', new EventVenueRow(['ticket_id' => null]));

        $method = new ReflectionMethod(ApiController::class, 'seatMatchesRequiredTicket');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $seat, [145 => true]));
    }
}
