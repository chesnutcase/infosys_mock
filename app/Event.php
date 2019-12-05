<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'picture', 'title', 'description', 'speaker_images', 'description', 'max_pax', 'location', 'start', 'end',
    ];

    protected $dates = [
        'start', 'end',
    ];

    protected $appends = [
        'current_attendees',
    ];

    public function getPictureAttribute($value)
    {
        return \Storage::disk('s3')->url($value);
    }

    public function getSpeakerImagesAttribute($value)
    {
        $getS3URL = function ($p) {
            return \Storage::disk('s3')->url($p);
        };

        return array_map($getS3URL, explode(';', $value));
    }

    public function setSpeakerImagesAttribute($value)
    {
        $this->attributes['speaker_images'] = implode(';', $value);
    }

    public function getCurrentAttendeesAttribute()
    {
        return $this->attendees()->count();
    }

    public function attendees()
    {
        return $this->hasMany("App\Attendee");
    }

    public function setStartAttribute($value)
    {
        $this->attributes['start'] = Carbon::createFromFormat('Y-m-d H:i', $value);
    }

    public function setEndAttribute($value)
    {
        $this->attributes['end'] = Carbon::createFromFormat('Y-m-d H:i', $value);
    }

    public function getStartAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i');
    }

    public function getEndAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i');
    }
}
