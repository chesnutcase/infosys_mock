<?php

namespace App\Mail;

use App\Attendee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisteredForEvent extends Mailable
{
    use Queueable, SerializesModels;

    public $event;
    public $qr_link;
    public $selfie;
    public $unregister_link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Attendee $attendee, String $qr_link = null)
    {
        $this->event = $attendee->event;
        if(!is_null($qr_link)){
            $this->qr_link = $qr_link;
        }
        if(!is_null($attendee->face)){
            $this->selfie = true;
        }
        $this->unregister_link = $attendee->getUnregisterURL();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env("MAIL_FROM"))
                    ->view('emails.registered_for_event');
    }
}
