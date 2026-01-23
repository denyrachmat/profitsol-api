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

use App\Traits\PORTAL\GencodeTraits;

class DLVSMTTYOEmailQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GencodeTraits;

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
        $to = [];

        $cc = [];

        $toList = $this->getDataGencode(
            'DLV_TYO_MAIL',
            [
                'pgm_value' => 'to',
            ],
            [
                'email' => 'pgm_value2',
            ],
            [],
        );

        $ccList = $this->getDataGencode(
            'DLV_TYO_MAIL',
            [
                'pgm_value' => 'cc',
            ],
            [
                'email' => 'pgm_value2',
            ],
            [],
        );

        if (count($toList) > 0) {
            foreach ($toList as $item) {
                array_push($to, $item->email);
            }
        }

        if (count($ccList) > 0) {
            foreach ($ccList as $item) {
                array_push($cc, $item->email);
            }
        }        

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
