<?php

namespace App\Mail\HRMS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class formNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $user, $formDet;
    
    public function __construct($user, $formDet)
    {
        $this->user = $user;
        $this->formDet = $formDet;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('HRMS Form Invitation')->view('HRMS.Email.FormNotification', ['user' => $this->user, 'form' => $this->formDet]);
    }
}
