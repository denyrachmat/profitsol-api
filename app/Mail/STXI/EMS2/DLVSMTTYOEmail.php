<?php

namespace App\Mail\STXI\EMS2;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DLVSMTTYOEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
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
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('STXI.EMS2.dlvMethodFromSMTtoTYO', [
            'user' => 'PT SMT Indonesia',
            'data' => $this->data,
            'totalDelivery' => $this->totalDelivery,
            'totalWBarcode' => $this->totalWBarcode,
            'totalWOBarcode' => $this->totalWOBarcode,
            'totalSMTDlv' => $this->totalSMTDlv,
            'date' => $this->date
        ])
            ->subject('STX-I Server Notification - Delivery to SMT - TYO Method');
    }
}
