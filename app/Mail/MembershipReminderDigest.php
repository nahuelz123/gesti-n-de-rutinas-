<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

class MembershipReminderDigest extends Mailable
{
    public function __construct(public Collection $reminders, public int $total, public CarbonInterface $generatedAt) {}

    public function build(): static
    {
        return $this->subject('VisionFit · Membresías por revisar · '.$this->generatedAt->format('d/m/Y'))->view('emails.membership-reminder-digest');
    }
}
