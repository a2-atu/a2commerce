<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\A2Commerce;
use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;

class A2CommerceUpdateCommand extends Command
{
    protected $signature = 'a2commerce:update {--skip-env : Do not modify .env files}';

    protected $description = 'Update A2Commerce package files (refreshes stubs and ensures env keys)';

    public function handle(Installer $installer): int
    {
        $this->displayHeader();
        
        $touchEnv = !$this->option('skip-env');
        
        $this->warn('⚠️  WARNING: This will refresh A2Commerce stubs with fresh copies from the package.');
        $this->warn('   Your custom modifications to copied files will be overwritten.');
        $this->warn('   Business logic in your app layer (Services, Controllers, etc.) is not affected.');
        $this->newLine();
        
        if (!$this->confirm('Do you want to continue with the update?', false)) {
            $this->info('❌ Update cancelled.');
            return self::SUCCESS;
        }
        
        // Step 1: Refresh stubs
        $this->step('Refreshing A2Commerce files and stubs...');
        $results = $installer->update($touchEnv);
        
        $copiedCount = count($results['copied']['copied']);
        $skippedCount = count($results['copied']['skipped'] ?? []);
        
        if ($copiedCount > 0) {
            $this->info("✅ {$copiedCount} file(s) refreshed successfully.");
        }
        
        if ($skippedCount > 0) {
            $this->warn("⚠️  {$skippedCount} file(s) skipped (files don't exist or couldn't be overwritten).");
        }
        
        // Step 2: Environment variables
        $this->step('Checking environment files...');
        if (!$touchEnv) {
            $this->line('   ⏭️  Environment keys skipped (--skip-env flag used).');
        } else {
            $envUpdated = false;
            foreach ($results['env'] as $file => $keys) {
                if ($keys !== []) {
                    $this->info("   ✅ Added to " . basename($file) . ": " . implode(', ', $keys));
                    $envUpdated = true;
                }
            }
            if (!$envUpdated) {
                $this->info('   ✅ Environment files already contain all A2Commerce keys.');
            }
        }
        
        // Step 3: Routes
        $this->step('Verifying API routes...');
        $routes = $results['routes'] ?? [];
        if ($routes !== []) {
            if ($routes['added'] ?? false) {
                $this->info('   ✅ PayPal webhook route added to routes/api.php');
            } else {
                $this->info('   ✅ PayPal webhook route already exists in routes/api.php');
            }
        }
        
        $this->displayCompletionMessage();
        
        return self::SUCCESS;
    }
    
    /**
     * Display the header
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('🔄 Updating A2Commerce Package...');
        $this->line('   Version: ' . A2Commerce::VERSION);
        $this->newLine();
    }
    
    /**
     * Display a step message
     */
    private function step(string $message): void
    {
        $this->info("📦 {$message}");
    }
    
    /**
     * Display completion message
     */
    private function displayCompletionMessage(): void
    {
        $this->newLine();
        $this->info('🎉 A2Commerce package updated successfully!');
        $this->newLine();
        
        $this->comment('📋 What was updated:');
        $this->line('   ✅ All package stubs refreshed with latest versions');
        $this->line('   ✅ Environment keys verified');
        $this->line('   ✅ API routes verified');
        $this->newLine();
        
        $this->comment('📖 Next steps:');
        $this->line('   1. Review any changes in the refreshed files');
        $this->line('   2. Test your application to ensure everything works correctly');
        $this->line('   3. Run migrations if there are any new ones: php artisan migrate');
        $this->newLine();
        
        $this->info('✨ Your A2Commerce package is now up to date!');
    }
}

