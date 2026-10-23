<?php

namespace App\Console\Commands;

use App\Services\CommercialReminderDispatcher;
use Illuminate\Console\Command;

class SendMembershipReminders extends Command
{
    protected $signature = 'memberships:remind';

    protected $description = 'Send the configured operator one daily summary of manual membership expirations';

    public function handle(CommercialReminderDispatcher $dispatcher): int
    {
        $result = $dispatcher->dispatch();
        $this->line('Membership reminder: '.$result.'.');

        return $result === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
