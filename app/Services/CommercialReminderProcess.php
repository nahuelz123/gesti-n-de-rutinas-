<?php

namespace App\Services;

use App\Models\CommercialSettings;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class CommercialReminderProcess
{
    private ?InvokedProcess $process = null;

    private int $nextCheck = 0;

    public function tick(): void
    {
        if ($this->process) {
            try {
                $this->process->ensureNotTimedOut();
                if ($this->process->running()) {
                    return;
                }
                if ($this->process->wait()->failed()) {
                    Log::warning('Membership reminder command did not complete successfully.');
                }
                $this->process = null;
            } catch (Throwable $exception) {
                try {
                    $this->process->stop(0);
                } catch (Throwable) { /* Keep workout polling available. */
                }
                $this->process = null;
                Log::warning('Membership reminder process stopped without confirmation.');
            }
        }
        if (now()->timestamp < $this->nextCheck) {
            return;
        }
        $this->nextCheck = now()->timestamp + 900;
        try {
            if (now()->hour < 9 || now()->hour >= 21 || ! (CommercialSettings::values()['reminders_enabled'] ?? false)) {
                return;
            }
            $this->process = Process::path(base_path())->timeout(60)->quietly()->start([PHP_BINARY, base_path('artisan'), 'memberships:remind', '--no-interaction']);
        } catch (Throwable $exception) {
            Log::warning('Membership reminder process could not start.', ['exception' => $exception::class]);
        }
    }
}
