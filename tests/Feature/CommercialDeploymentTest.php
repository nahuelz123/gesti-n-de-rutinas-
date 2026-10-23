<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommercialDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_command_accepts_a_complete_schema(): void
    {
        $this->artisan('visionfit:prepare')->assertExitCode(0);
    }

    public function test_prepare_command_refuses_a_schema_with_a_missing_commercial_table(): void
    {
        Schema::drop('commercial_payments');
        $this->artisan('visionfit:prepare')->assertExitCode(1);
    }
}
