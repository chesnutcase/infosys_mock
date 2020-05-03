<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attendee extends Model
{
    protected $fillable = [
        'event_id', 'email', 'name', 'waitlist_no', 'delete_nonce'
    ];

    public function event(){
        return $this->belongsTo("App\Event");
    }

    public function getUnregisterURL(){
        return env("APP_URL") . "/attendee/" . $this->id . "/deregister/" . $this->delete_nonce;
    }
}
