<?php

namespace Tests\Feature;

use App\Models\CommercialSettings;
use App\Services\CommercialReminderProcess;
use Carbon\Carbon;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\InvokedProcess;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CommercialReminderProcessTest extends TestCase
{
    use RefreshDatabase;

    private function pending(InvokedProcess $process, int $times = 1): void
    {
        $pending = Mockery::mock(PendingProcess::class);
        Process::shouldReceive('path')->times($times)->with(base_path())->andReturn($pending);
        $pending->shouldReceive('timeout')->times($times)->with(60)->andReturnSelf();
        $pending->shouldReceive('quietly')->times($times)->andReturnSelf();
        $pending->shouldReceive('start')->times($times)->with([PHP_BINARY, base_path('artisan'), 'memberships:remind', '--no-interaction'])->andReturn($process);
    }

    public function test_slow_mail_process_does_not_block_or_spawn_a_second_process(): void
    {
        $this->travelTo(Carbon::parse('2027-01-31 09:00:00'));
        CommercialSettings::saveValues(['reminders_enabled' => true]);
        $process = Mockery::mock(InvokedProcess::class);
        $process->shouldReceive('ensureNotTimedOut')->twice();
        $process->shouldReceive('running')->twice()->andReturnTrue();
        $process->shouldNotReceive('wait');
        $this->pending($process);
        $worker = app(CommercialReminderProcess::class);
        $worker->tick();
        $worker->tick();
        $this->travel(16)->minutes();
        $worker->tick();
    }

    public function test_timeout_is_stopped_without_escaping_into_the_rest_worker(): void
    {
        $this->travelTo(Carbon::parse('2027-01-31 09:00:00'));
        CommercialSettings::saveValues(['reminders_enabled' => true]);
        $process = Mockery::mock(InvokedProcess::class);
        $process->shouldReceive('ensureNotTimedOut')->once()->andThrow(new RuntimeException('timeout'));
        $process->shouldReceive('stop')->once()->with(0)->andReturn(1);
        $this->pending($process);
        $worker = app(CommercialReminderProcess::class);
        $worker->tick();
        $worker->tick();
        $this->artisan('rest-alerts:work', ['--once' => true])->assertSuccessful();
    }

    public function test_completed_child_is_reaped_and_checks_are_spaced_fifteen_minutes(): void
    {
        $this->travelTo(Carbon::parse('2027-01-31 09:00:00'));
        CommercialSettings::saveValues(['reminders_enabled' => true]);
        $process = Mockery::mock(InvokedProcess::class);
        $result = Mockery::mock(ProcessResult::class);
        $result->shouldReceive('failed')->once()->andReturnFalse();
        $process->shouldReceive('ensureNotTimedOut')->once();
        $process->shouldReceive('running')->once()->andReturnFalse();
        $process->shouldReceive('wait')->once()->andReturn($result);
        $this->pending($process, 2);
        $worker = app(CommercialReminderProcess::class);
        $worker->tick();
        $worker->tick();
        $this->travel(14)->minutes();
        $worker->tick();
        $this->travel(1)->minutes();
        $worker->tick();
    }

    public function test_once_mode_does_not_launch_mail_and_default_disabled_mode_stays_idle(): void
    {
        Process::fake();
        $this->travelTo(Carbon::parse('2027-01-31 09:00:00'));
        app(CommercialReminderProcess::class)->tick();
        $this->artisan('rest-alerts:work', ['--once' => true])->assertSuccessful();
        Process::assertNothingRan();
    }
}
