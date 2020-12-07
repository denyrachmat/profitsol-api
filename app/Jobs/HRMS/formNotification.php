<?php

namespace App\Jobs\HRMS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\HRMS\formNotification as formNotificationMail;
use Illuminate\Support\Facades\Mail;

class formNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $to, $user, $formDet;
    
    public function __construct($to, $user, $formDet)
    {
        $this->to = $to;
        $this->user = $user;
        $this->formDet = $formDet;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->to)->send(new formNotificationMail($this->user, $this->formDet));
    }
}
