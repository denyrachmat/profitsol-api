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
    public $from, $to, $subject, $isApprove, $content, $token, $dataVar;
    public function __construct($from, $to, $subject = 'AMS Notification', $isApprove = 0, $content = '', $token = '', $dataVar = [])
    {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->isApprove = $isApprove;
        $this->content = $content;
        $this->token = $token;
        $this->dataVar = $dataVar;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mail::to($this->to)
        //     ->cc($this->cc)
        //     ->send(new EmailNotification($this->subject, $this->content));

        $getUsersFrom = PortalUserDet::where('u_username', $this->from)->first();
        $getUsers = PortalUserDet::where('u_username', $this->to)->first();

        // Convert fullname Recepient variable
        $convertContent = str_replace(search: "{{recipient_fullname}}", replace: $this->to, subject: $this->content);
        if (!empty($getUsers)) {
            $convertContent = str_replace(search: "{{recipient_fullname}}", replace: "{$getUsers->pud_first_name} {$getUsers->pud_last_name}", subject: $this->content);
        }

        // Convert fullname sender variable
        $convertContent = str_replace(search: "{{fullname}}", replace: $this->from, subject: $this->content);
        if (!empty($getUsers)) {
            $convertContent = str_replace(search: "{{fullname}}", replace: "{$getUsers->pud_first_name} {$getUsers->pud_last_name}", subject: $this->content);
        }

        $convertContent = str_replace(search: "{{linkapproval}}", replace: config('app.fe_url', "http://192.168.100.32:8081/portal_v2/#")."/ams/approvalAction/{$this->token}", subject: $convertContent);

        foreach ($this->dataVar as $keyVar => $valueVar) {
            $convertContent = str_replace(search: "{{".$keyVar."}}", replace: $valueVar, subject: $convertContent);
        }

        Notification::route('mail', $this->to)->notify(new ApprovalNotification(
            $this->to,
            $this->subject,
            $this->isApprove,
            $convertContent,
            $this->token
        ));
    }
}
