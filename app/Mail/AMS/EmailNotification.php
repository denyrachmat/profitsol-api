<?php

namespace App\Mail\AMS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PORTAL\PortalUserDet;

class EmailNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $subject, $content;
    /**
     * Create a new message instance.
     */
    public function __construct($to, $subject, $content)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->content = $content;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'AMS.AMSEmailTemplate',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $getUsers = PortalUserDet::where('u_username', $this->to)->first();

        // Convert fullname variable
        $convertContent = str_replace(search: "{{fullname}}", replace: $this->to, subject: $this->content);
        if (!empty($getUsers)) {
            $convertContent = str_replace(search: "{{fullname}}", replace: "{$getUsers->pud_first_name} {$getUsers->pud_first_name}", subject: $this->content);
        }

        $convertContent = str_replace(search: "{{linkapproval}}", replace: "{$getUsers->pud_first_name} {$getUsers->pud_first_name}", subject: $this->content);

        return [
            'content' => $this->content
        ];
    }
}
