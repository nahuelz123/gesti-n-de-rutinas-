<?php

namespace App\Mail;

use App\Models\CommercialInquiry;
use Illuminate\Mail\Mailable;

class CommercialReceipt extends Mailable
{
    public function __construct(public CommercialInquiry $inquiry, public bool $internal = false) {}

    public function build(): static
    {
        return $this->subject('VisionFit · '.($this->internal ? 'Nueva solicitud' : 'Solicitud recibida').' · '.$this->inquiry->reference)
            ->view('emails.commercial-receipt');
    }
}
