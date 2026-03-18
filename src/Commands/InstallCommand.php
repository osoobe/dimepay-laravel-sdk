<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Commands;

use Illuminate\Console\Command;
use Spatie\WebhookClient\WebhookClientServiceProvider;

class InstallCommand extends Command
{
    protected $signature = 'dimepay:install';

    protected $description = 'Install the DimePay Laravel SDK — publishes config and prints setup instructions.';

    public function handle(): int
    {
        $this->info('Installing DimePay Laravel SDK...');
        $this->newLine();

        // Publish config
        $this->callSilently('vendor:publish', [
            '--tag' => 'dimepay-config',
            '--force' => false,
        ]);
        $this->line('  <fg=green;options=bold>✓</> Published <fg=cyan>config/dimepay.php</>');

        // Publish webhook-client config if available
        if (class_exists(WebhookClientServiceProvider::class)) {
            $this->callSilently('vendor:publish', [
                '--provider' => 'Spatie\WebhookClient\WebhookClientServiceProvider',
                '--tag' => 'webhook-client-config',
                '--force' => false,
            ]);
            $this->line('  <fg=green;options=bold>✓</> Published <fg=cyan>config/webhook-client.php</>');

            $this->callSilently('vendor:publish', [
                '--provider' => 'Spatie\WebhookClient\WebhookClientServiceProvider',
                '--tag' => 'webhook-client-migrations',
                '--force' => false,
            ]);
            $this->line('  <fg=green;options=bold>✓</> Published webhook migrations');
        }

        $this->newLine();
        $this->line('<fg=yellow;options=bold>Next steps:</>');
        $this->newLine();

        $this->line('  1. Add your DimePay credentials to <fg=cyan>.env</>:');
        $this->newLine();
        $this->line('     <fg=gray>DIMEPAY_ENV=sandbox</>');
        $this->line('     <fg=gray>DIMEPAY_CLIENT_KEY=ck_your_client_key</>');
        $this->line('     <fg=gray>DIMEPAY_SECRET_KEY=sk_your_secret_key</>');
        $this->line('     <fg=gray>DIMEPAY_WEBHOOK_SECRET=your_webhook_secret</>');
        $this->newLine();

        $this->line('  2. Configure <fg=cyan>config/webhook-client.php</> — find the <fg=cyan>dimepay</> entry and set:');
        $this->newLine();
        $this->line('     <fg=gray>\'signature_validator\' => \Osoobe\DimePay\Webhooks\DimePaySignatureValidator::class,</>');
        $this->line('     <fg=gray>\'webhook_profile\'     => \Osoobe\DimePay\Webhooks\DimePayWebhookProfile::class,</>');
        $this->line('     <fg=gray>\'process_webhook_job\' => \Osoobe\DimePay\Webhooks\ProcessDimePayWebhookJob::class,</>');
        $this->newLine();

        $this->line('  3. Run migrations:');
        $this->newLine();
        $this->line('     <fg=gray>php artisan migrate</>');
        $this->newLine();

        $this->line('  4. Exclude the webhook route from CSRF in <fg=cyan>bootstrap/app.php</>:');
        $this->newLine();
        $this->line('     <fg=gray>->withMiddleware(function (Middleware $middleware) {</>');
        $this->line('     <fg=gray>    $middleware->validateCsrfTokens(except: [\'dimepay/webhook\']);</>');
        $this->line('     <fg=gray>})</>');
        $this->newLine();

        $this->info('DimePay Laravel SDK installed successfully.');
        $this->newLine();
        $this->line('  Docs: <fg=cyan>https://github.com/osoobe/dimepay-laravel-sdk</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
