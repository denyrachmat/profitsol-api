<?php

namespace App\Jobs\DMS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\DMS\ReminderPendingApproval;
use Illuminate\Support\Facades\Mail;

class ReminderPendingApprovalJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $user;
    protected $userFrom;
    protected $data;

    public function __construct($data, $user, $userFrom)
    {
        $this->user = $user;
        $this->userFrom = $userFrom;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mail = Mail::to($this->user->email);

        if ($this->userFrom->email === $this->data[0]->users->email) {
            $mail
            ->cc($this->userFrom->email)
            ->send(new ReminderPendingApproval($this->user, $this->data));
        } else {
            $mail
            ->cc([$this->userFrom->email, $this->data[0]->users->email])
            ->send(new ReminderPendingApproval($this->user, $this->data));
        }
    }
}
