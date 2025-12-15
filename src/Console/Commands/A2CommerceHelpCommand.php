<?php

namespace A2\A2Commerce\Console\Commands;

use Illuminate\Console\Command;

class A2CommerceHelpCommand extends Command
{
    protected $signature = 'a2commerce:help';

    protected $description = 'Show available A2Commerce commands and required env keys';

    public function handle(): int
    {
        $this->line('A2Commerce commands:');
        $this->line('  a2commerce:install    Install stubs and add env keys');
        $this->line('  a2commerce:update     Refresh stubs and ensure env keys');
        $this->line('  a2commerce:uninstall  Remove stubs and env keys');
        $this->line('  a2commerce:help       Show this help');
        $this->newLine();

        $this->line('Env keys added during install/update:');
        $this->line('  # A2 CONFIGURATION');
        $this->line('  A2_PAYPAL_MODE=sandbox');
        $this->line('  A2_PAYPAL_SECRET=');
        $this->line('  A2_PAYPAL_CLIENT_ID=');
        $this->line('  A2_PAYPAL_WEBHOOK_ID=');
        $this->line('  A2_ORDER_PREFIX="SP-OD"');
        $this->line('  A2_TAX_RATE=0');
        $this->line('  A2_SHIPPING_FEE=0');
        $this->line('  A2_CURRENCY=USD');
        $this->line('  A2_CURRENCY_SYMBOL="$"');
        $this->line('  A2_CURRENCY_CONVERSION_RATE=130');
        $this->newLine();
        $this->line('Routes appended to routes/api.php:');
        $this->line('  Route::prefix(\'a2/payment\')->group(function () {');
        $this->line('      Route::post(\'/paypal/webhook\', [\\App\\Http\\Controllers\\A2\\Commerce\\PaymentController::class, \'webhookPayPal\'])->name(\'api.payment.paypal.webhook\');');
        $this->line('  });');

        return self::SUCCESS;
    }
}

