<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'picture', 'title', 'description', 'speaker_images', 'description', 'max_pax', 'location', 'start', 'end',
    ];

    protected $dates = [
        'start', 'end',
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

    /*
        public function setStartAttribute($value)
        {
            return \Carbon\Carbon::parse($value);
        }

        public function setEndAttribute($value)
        {
            return \Carbon\Carbon::parse($value);
        }

        public function getStartAttribute($value)
        {
            return $value->format('Y-m-d H:i');
        }

        public function getEndAttribute($value)
        {
            return $value->format('Y-m-d H:i');
        }
        **/
}
