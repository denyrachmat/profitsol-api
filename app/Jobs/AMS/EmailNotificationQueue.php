<?php

namespace App\Jobs\AMS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Illuminate\Support\Facades\Notification;

use App\Mail\AMS\EmailNotification;
use App\Notifications\AMS\ApprovalNotification;

class EmailNotificationQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $to, $subject, $isApprove, $content, $token;
    public function __construct($to, $subject = 'AMS Notification', $isApprove = 0, $content = '', $token = '')
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->isApprove = $isApprove;
        $this->content = $content;
        $this->token = $token;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mail::to($this->to)
        //     ->cc($this->cc)
        //     ->send(new EmailNotification($this->subject, $this->content));

        Notification::send($this->to, new ApprovalNotification(
            $this->to,
            $this->subject,
            $this->isApprove,
            $this->content,
            $this->token
        ));
    }
}
