<?php

namespace App\Jobs\PORTAl;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class notifSentQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $from;
    protected $to;
    protected $title;
    protected $message;
    protected $link;
    protected $icon;
    protected $hashIdLocation;
    protected $startDate;
    protected $endDate;
    protected $type;
    protected $loc;
    protected $graph;
    /**
     * Create a new job instance.
     */
    public function __construct($from, $to, $title, $message, $startDate, $endDate, $type, $loc, $link = '', $icon = '', $hashIdLocation = '', $graph = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->title = $title;
        $this->message = $message;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->loc = $loc;
        $this->link = $link;
        $this->icon = $icon;
        $this->hashIdLocation = $hashIdLocation;
        $this->graph = $graph;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notifController = app(\App\Http\Controllers\API\PORTAL\NotifController::class);
        $notifController->store(new Request([
            'pnm_from_users' => $this->from,
            'pnm_to_users' => $this->to,
            'pnm_title' => $this->title,
            'pnm_message' => $this->message,
            'pnm_link' => $this->link,
            'pnm_icon' => $this->icon,
            'pnm_hash_id_location' => $this->hashIdLocation,
            'pnm_start_date' => $this->startDate,
            'pnm_end_date' => $this->endDate,
            'pnm_notif_loc' => $this->loc,
            'pnm_type' => $this->type,
            'graph' => $this->graph,
        ]));
    }
}
