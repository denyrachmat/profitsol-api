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
use App\Models\PORTAL\PortalUserDet;

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

        $getUsers = PortalUserDet::where('u_username', $this->to)->first();

        // Convert fullname variable
        $convertContent = str_replace(search: "{{fullname}}", replace: $this->to, subject: $this->content);
        if (!empty($getUsers)) {
            $convertContent = str_replace(search: "{{fullname}}", replace: "{$getUsers->pud_first_name} {$getUsers->pud_first_name}", subject: $this->content);
        }

        $convertContent = str_replace(search: "{{linkapproval}}", replace: env('FE_URL')."/approvalAction/{$this->token}", subject: $convertContent);

        Notification::route('mail', $this->to)->notify(new ApprovalNotification(
            $this->to,
            $this->subject,
            $this->isApprove,
            $convertContent,
            $this->token
        ));
    }
}
