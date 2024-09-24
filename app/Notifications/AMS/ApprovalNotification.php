<?php

namespace App\Notifications\AMS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\PORTAL\PortalUserDet;

class ApprovalNotification extends Notification
{
    use Queueable;

    public $to, $subject, $isApprove, $content, $token;
    /**
     * Create a new notif instance.
     */
    public function __construct($to, $subject, $isApprove, $content, $token)
    {
        $getUsers = PortalUserDet::where('u_username', $to)->first();

        // Convert fullname variable
        $convertContent = str_replace(search: "{{fullname}}", replace: $to, subject: $content);
        if (!empty($getUsers)) {
            $convertContent = str_replace(search: "{{fullname}}", replace: "{$getUsers->pud_first_name} {$getUsers->pud_first_name}", subject: $content);
        }

        $convertContent = str_replace(search: "{{linkapproval}}", replace: "{env('FE_URL')}/approvalAction/{$token}", subject: $content);

        $this->to = $to;
        $this->subject = $subject;
        $this->isApprove = $isApprove;
        $this->content = $convertContent;
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $getUsers = PortalUserDet::where('u_username', $this->to)->first();
        return (new MailMessage)
                    ->subject($this->subject)
                    ->markdown('AMS.AMSEmailTemplate',[
                        'users' => $getUsers,
                        'data' => $this,
                        'approve' => url('/'),
                        'reject' => url('/')
                    ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
