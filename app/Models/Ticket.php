<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SeatTable;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    protected $fillable = [
        'event_id',
        'user_id',
        'ticket_number',
        'name',
        'type',
        'quantity',
        'ticket_per_order',
        'start_time',
        'end_time',
        'price',
        'description',
        'status',
        'is_deleted',
        'allday',
        'maximum_checkins',
        'seatmap_id',
        'is_add_on',
        'tax_id',
        'SeatTable_id'
    ];

    protected $dates = ['start_time', 'end_time'];

    /**
     * Relation: Ticket belongs to Event
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function allowUser()
    {
        return $this->hasOne(TicketAllowUser::class, 'ticket_id', 'id');
    }

    public function getAllowToUserAttribute()
    {
        if ($this->relationLoaded('allowUser')) {
            return $this->allowUser ? (int) $this->allowUser->allow_to_user : 1;
        }
        $record = TicketAllowUser::where('ticket_id', $this->id)->first();
        return $record ? (int) $record->allow_to_user : 1;
    }

    /**
     * Override the __get method to handle seatTable access
     */
    public function __get($key)
    {
        if ($key === 'seatTable') {
            return $this->getFirstSeatTableAttribute();
        }

        return parent::__get($key);
    }

    /**
     * Override the __call method to handle dynamic relationship calls
     */
    public function __call($method, $parameters)
    {
        if ($method === 'seatTable') {
            return $this->getFirstSeatTableAttribute();
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Override getAttribute to handle seatTable
     */
    public function getAttribute($key)
    {
        if ($key === 'seatTable') {
            return $this->getFirstSeatTableAttribute();
        }

        return parent::getAttribute($key);
    }

    /**
     * Override the relationLoaded method to handle seatTable
     */
    public function relationLoaded($key)
    {
        if ($key === 'seatTable') {
            return true; // Always return true to prevent loading attempts
        }

        return parent::relationLoaded($key);
    }

    /**
     * Accessor: Get all related SeatTables
     */
    public function getSeatTablesAttribute()
    {
        if (empty($this->SeatTable_id)) {
            return collect([]);
        }

        // Handle comma-separated string
        $ids = is_string($this->SeatTable_id) ? explode(',', $this->SeatTable_id) : (array) $this->SeatTable_id;
        $ids = array_filter($ids); // Remove empty values
        
        return SeatTable::whereIn('id', $ids)->get();
    }

    /**
     * Accessor: Get first SeatTable
     */
    public function getFirstSeatTableAttribute()
    {
        if (empty($this->SeatTable_id)) {
            return null;
        }

        // Handle comma-separated string
        $ids = is_string($this->SeatTable_id) ? explode(',', $this->SeatTable_id) : (array) $this->SeatTable_id;
        $firstId = $ids[0] ?? null;
        return $firstId ? SeatTable::find($firstId) : null;
    }
    public function seatTable()
    {
        return $this->belongsTo(SeatTable::class, 'seatTable_id'); // Adjust foreign key if needed
    }
}
