<?php

namespace App\Jobs\STXI\EMS2;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Mail\STXI\EMS2\DLVSMTTYOEmail;
use Illuminate\Support\Facades\Mail;

class DLVSMTTYOEmailQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $user;
    protected $data;
    protected $totalDelivery;
    protected $totalWBarcode;
    protected $totalWOBarcode;
    protected $totalSMTDlv;
    protected $date;

    public function __construct(
        $user,
        $data,
        $totalDelivery,
        $totalWBarcode,
        $totalWOBarcode,
        $totalSMTDlv,
        $date
    )
    {
        $this->user = $user;
        $this->data = $data;
        $this->totalDelivery = $totalDelivery;
        $this->totalWBarcode = $totalWBarcode;
        $this->totalWOBarcode = $totalWOBarcode;
        $this->totalSMTDlv = $totalSMTDlv;
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $to = [
            'nidya-sianty@sumitronics.co.jp'
        ];

        $cc = [
            'deny-rachmat@sumitronics.co.jp',
        ];

        Mail::to($to)
            ->cc($cc)
            ->send(new DLVSMTTYOEmail(
                $this->user,
                $this->data,
                $this->totalDelivery,
                $this->totalWBarcode,
                $this->totalWOBarcode,
                $this->totalSMTDlv,
                $this->date,
            ));
    }
}
