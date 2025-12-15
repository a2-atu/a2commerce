<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\A2Commerce;
use Illuminate\Console\Command;

class A2CommerceHelpCommand extends Command
{
    protected $signature = 'a2commerce:help';

    protected $description = 'Display help information for A2Commerce package commands';

    public function handle(): int
    {
        $this->displayHeader();
        $this->displayCommands();
        $this->displayUsageExamples();
        $this->displayEnvironmentKeys();
        $this->displayRoutes();
        $this->displayFooter();

        return self::SUCCESS;
    }
    
    /**
     * Display the header
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                      A2COMMERCE HELP                         ║');
        $this->info('║                      Version ' . str_pad(A2Commerce::VERSION, 25) . '║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        $this->comment('🛒 A2Commerce is a decoupled, event-driven e-commerce stack');
        $this->comment('   for Laravel with cart, checkout, PayPal integration, and');
        $this->comment('   guest checkout support, built with Vormia and Livewire Volt.');
        $this->newLine();
    }
    
    /**
     * Display available commands
     */
    private function displayCommands(): void
    {
        $this->info('📋 AVAILABLE COMMANDS:');
        $this->newLine();
        
        $commands = [
            [
                'command' => 'a2commerce:install',
                'description' => 'Install A2Commerce package with all files and configurations',
                'options' => '--no-overwrite (keep existing files), --skip-env (leave .env untouched)'
            ],
            [
                'command' => 'a2commerce:update',
                'description' => 'Update package files (refreshes stubs and ensures env keys)',
                'options' => '--skip-env (leave .env untouched)'
            ],
            [
                'command' => 'a2commerce:uninstall',
                'description' => 'Remove all A2Commerce package files and configurations',
                'options' => '--keep-env (preserve env keys), --force (skip confirmation prompts)'
            ],
            [
                'command' => 'a2commerce:help',
                'description' => 'Display this help information',
                'options' => null
            ]
        ];
        
        foreach ($commands as $cmd) {
            $this->line("  <fg=green>{$cmd['command']}</>");
            $this->line("    {$cmd['description']}");
            if ($cmd['options']) {
                $this->line("    <fg=yellow>Options:</> {$cmd['options']}");
            }
            $this->newLine();
        }
    }
    
    /**
     * Display usage examples
     */
    private function displayUsageExamples(): void
    {
        $this->info('💡 USAGE EXAMPLES:');
        $this->newLine();
        
        $examples = [
            [
                'title' => 'Installation',
                'command' => 'php artisan a2commerce:install',
                'description' => 'Install A2Commerce with all files and configurations'
            ],
            [
                'title' => 'Install (Preserve Existing Files)',
                'command' => 'php artisan a2commerce:install --no-overwrite',
                'description' => 'Install without overwriting existing files'
            ],
            [
                'title' => 'Install (Skip Environment)',
                'command' => 'php artisan a2commerce:install --skip-env',
                'description' => 'Install without modifying .env files'
            ],
            [
                'title' => 'Update Package',
                'command' => 'php artisan a2commerce:update',
                'description' => 'Refresh all package files to latest version'
            ],
            [
                'title' => 'Uninstall Package',
                'command' => 'php artisan a2commerce:uninstall',
                'description' => 'Remove all A2Commerce files and configurations'
            ],
            [
                'title' => 'Uninstall (Keep Environment)',
                'command' => 'php artisan a2commerce:uninstall --keep-env',
                'description' => 'Uninstall but preserve environment variables'
            ],
            [
                'title' => 'Force Uninstall',
                'command' => 'php artisan a2commerce:uninstall --force',
                'description' => 'Uninstall without confirmation prompts'
            ]
        ];
        
        foreach ($examples as $example) {
            $this->line("  <fg=cyan>{$example['title']}:</>");
            $this->line("    <fg=white>{$example['command']}</>");
            $this->line("    <fg=gray>{$example['description']}</>");
            $this->newLine();
        }
    }
    
    /**
     * Display environment keys
     */
    private function displayEnvironmentKeys(): void
    {
        $this->info('⚙️  ENVIRONMENT VARIABLES:');
        $this->newLine();
        
        $this->line('  <fg=white>These keys are added to .env and .env.example during installation:</>');
        $this->newLine();
        
        $envKeys = [
            ['key' => 'A2_PAYPAL_MODE', 'value' => 'sandbox', 'description' => 'PayPal environment (sandbox or live)'],
            ['key' => 'A2_PAYPAL_SECRET', 'value' => '', 'description' => 'PayPal API secret key'],
            ['key' => 'A2_PAYPAL_CLIENT_ID', 'value' => '', 'description' => 'PayPal API client ID'],
            ['key' => 'A2_PAYPAL_WEBHOOK_ID', 'value' => '', 'description' => 'PayPal webhook ID'],
            ['key' => 'A2_ORDER_PREFIX', 'value' => 'SP-OD', 'description' => 'Order number prefix'],
            ['key' => 'A2_TAX_RATE', 'value' => '0', 'description' => 'Default tax rate (decimal)'],
            ['key' => 'A2_SHIPPING_FEE', 'value' => '0', 'description' => 'Default shipping fee (decimal)'],
            ['key' => 'A2_CURRENCY', 'value' => 'USD', 'description' => 'Default currency code'],
            ['key' => 'A2_CURRENCY_SYMBOL', 'value' => '$', 'description' => 'Currency symbol'],
            ['key' => 'A2_CURRENCY_CONVERSION_RATE', 'value' => '130', 'description' => 'Currency conversion rate'],
        ];
        
        $this->line('  <fg=cyan># A2 CONFIGURATION</>');
        foreach ($envKeys as $env) {
            $value = $env['value'] !== '' ? "={$env['value']}" : '=';
            $this->line("  <fg=white>{$env['key']}{$value}</>");
            $this->line("    <fg=gray>{$env['description']}</>");
        }
        
        $this->newLine();
    }
    
    /**
     * Display routes information
     */
    private function displayRoutes(): void
    {
        $this->info('🛣️  API ROUTES:');
        $this->newLine();
        
        $this->line('  <fg=white>The following route is added to routes/api.php:</>');
        $this->newLine();
        
        $this->line('  <fg=cyan>Route::prefix(\'a2/payment\')->group(function () {</>');
        $this->line('  <fg=cyan>    Route::post(\'/paypal/webhook\', [</>');
        $this->line('  <fg=cyan>        \\App\\Http\\Controllers\\A2\\Commerce\\PaymentController::class,</>');
        $this->line('  <fg=cyan>        \'webhookPayPal\'</>');
        $this->line('  <fg=cyan>    ])->name(\'api.payment.paypal.webhook\');</>');
        $this->line('  <fg=cyan>});</>');
        
        $this->newLine();
        $this->line('  <fg=gray>Endpoint: POST /a2/payment/paypal/webhook</>');
        $this->newLine();
    }
    
    /**
     * Display footer
     */
    private function displayFooter(): void
    {
        $this->info('📚 ADDITIONAL RESOURCES:');
        $this->newLine();
        
        $this->line('  <fg=white>Implementation Guide:</> packageflow-md/0-a_2_commerce_implementation_guide.md');
        $this->line('  <fg=white>Schema Documentation:</> packageflow-md/1-a_2_commerce_schema.md');
        $this->line('  <fg=white>Event Flow:</> packageflow-md/2-a_2_event_flow.md');
        $this->line('  <fg=white>Payment Guide:</> packageflow-md/3-a_2_payment_guide.md');
        $this->line('  <fg=white>Shipping Guides:</> packageflow-md/4-6*.md');
        
        $this->newLine();
        $this->comment('💡 For more detailed documentation, review the packageflow-md directory.');
        $this->newLine();
        
        $this->info('🎉 Thank you for using A2Commerce!');
        $this->newLine();
    }
}

