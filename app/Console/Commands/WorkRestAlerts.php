<?php

namespace App\Console\Commands;

use App\Services\CommercialReminderProcess;
use App\Services\RestAlertDispatcher;
use Illuminate\Console\Command;
use Throwable;

class WorkRestAlerts extends Command
{
    protected $signature = 'rest-alerts:work {--sleep=1 : Seconds between database polls} {--once : Process due alerts once and exit}';

    protected $description = 'Deliver durable rest alerts without relying on a browser timer or queue driver';

    public function handle(RestAlertDispatcher $dispatcher, CommercialReminderProcess $reminders): int
    {
        $sleep = min(5, max(1, (int) $this->option('sleep')));
        $lastPrune = 0;
        $announced = false;
        do {
            try {
                $dispatcher->dispatchDue();
                if (! $announced) {
                    $this->info('Rest alert worker connected; polling database every '.$sleep.' second(s).');
                    $announced = true;
                }
                if (time() - $lastPrune >= 3600) {
                    $dispatcher->prune();
                    $lastPrune = time();
                }
            } catch (Throwable $exception) {
                // Avoid dumping subscription-bearing network exceptions into deploy logs.
                $this->error('Rest alert polling failed; check database access and APP_KEY configuration.');
                if ($this->option('once')) {
                    return self::FAILURE;
                }
            }
            if ($this->option('once')) {
                break;
            }
            $reminders->tick();
            sleep($sleep);
        } while (true);

        return self::SUCCESS;
    }
}
