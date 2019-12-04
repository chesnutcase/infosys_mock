<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attendee extends Model
{
    protected $fillable = [
        'event_id', 'email', 'name', 'waitlist_no',
    ];
}
