<?php

namespace App\Console\Commands;

use Illuminate\Cache\DatabaseLock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrepareDeployment extends Command
{
    protected $signature = 'visionfit:prepare';

    protected $description = 'Aplica migraciones con exclusión entre servicios y verifica el esquema comercial.';

    public function handle(): int
    {
        try {
            // Use a shared SQL lock without an APP_NAME-dependent cache prefix: web and worker share the schema.
            $lock = new DatabaseLock(DB::connection(), 'cache_locks', 'visionfit-deployment-schema', 120);

            return $lock->block(60, function () {
                if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
                    return self::FAILURE;
                }
                $ready = Schema::hasTable('commercial_inquiries') && Schema::hasColumn('commercial_payments', 'provider_updated_at')
                    && Schema::hasColumns('commercial_subscriptions', ['public_id', 'paid_until', 'trial_ends_at'])
                    && Schema::hasColumn('gyms', 'billing_managed') && Schema::hasColumn('users', 'commercial_access_enabled');
                if (! $ready) {
                    $this->error('El esquema comercial está incompleto; se detiene esta publicación.');

                    return self::FAILURE;
                }
                $this->info('Esquema de VisionFit listo.');

                return self::SUCCESS;
            });
        } catch (\Throwable $exception) {
            $this->error('No se pudo preparar el despliegue: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
