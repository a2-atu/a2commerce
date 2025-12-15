<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\A2Commerce;
use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;

class A2CommerceUninstallCommand extends Command
{
    protected $signature = 'a2commerce:uninstall {--keep-env : Leave env keys untouched} {--force : Skip confirmation prompts}';

    protected $description = 'Remove all A2Commerce package files and configurations';

    public function handle(Installer $installer): int
    {
        $this->displayHeader();
        
        $force = $this->option('force');
        $keepEnv = $this->option('keep-env');
        
        // Warning message
        $this->error('⚠️  DANGER: This will remove A2Commerce from your application!');
        $this->warn('   This action will:');
        $this->warn('   • Remove all A2Commerce copied files and stubs');
        $this->warn('   • Remove PayPal webhook route from routes/api.php');
        if (!$keepEnv) {
            $this->warn('   • Remove A2Commerce environment variables from .env and .env.example');
        } else {
            $this->warn('   • Keep environment variables (--keep-env flag used)');
        }
        $this->warn('   • Note: Database tables and migrations are NOT removed');
        $this->warn('   • Note: Composer packages are NOT uninstalled');
        $this->newLine();
        
        if (!$force && !$this->confirm('Are you absolutely sure you want to uninstall A2Commerce?', false)) {
            $this->info('❌ Uninstall cancelled.');
            return self::SUCCESS;
        }
        
        // Final confirmation
        if (!$force) {
            $this->newLine();
            $this->error('🚨 FINAL WARNING: This action cannot be undone!');
            if (!$this->confirm('Type "yes" to proceed with uninstallation', false)) {
                $this->info('❌ Uninstall cancelled.');
                return self::SUCCESS;
            }
        }
        
        // Step 1: Remove files
        $this->step('Removing A2Commerce files and stubs...');
        $touchEnv = !$keepEnv;
        $results = $installer->uninstall($touchEnv);
        
        $removedCount = count($results['removed']);
        if ($removedCount > 0) {
            $this->info("✅ {$removedCount} file(s) removed successfully.");
        } else {
            $this->info('✅ No files to remove (files may have been manually deleted).');
        }
        
        // Step 2: Environment variables
        $this->step('Cleaning up environment files...');
        if ($keepEnv) {
            $this->line('   ⏭️  Environment keys preserved (--keep-env flag used).');
        } else {
            $envCleaned = false;
            foreach ($results['env'] as $file => $keys) {
                if ($keys !== []) {
                    $this->info("   ✅ Removed from " . basename($file) . ": " . implode(', ', $keys));
                    $envCleaned = true;
                }
            }
            if (!$envCleaned) {
                $this->info('   ✅ No A2Commerce environment keys found to remove.');
            }
        }
        
        // Step 3: Routes
        $this->step('Removing API routes...');
        $routes = $results['routes'] ?? [];
        if ($routes !== []) {
            if ($routes['removed'] ?? false) {
                $this->info('   ✅ PayPal webhook route removed from routes/api.php');
            } else {
                $this->info('   ✅ No route block found to remove.');
            }
        }
        
        $this->displayCompletionMessage($keepEnv);
        
        return self::SUCCESS;
    }
    
    /**
     * Display the header
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('🗑️  Uninstalling A2Commerce Package...');
        $this->line('   Version: ' . A2Commerce::VERSION);
        $this->newLine();
    }
    
    /**
     * Display a step message
     */
    private function step(string $message): void
    {
        $this->info("🗂️  {$message}");
    }
    
    /**
     * Display completion message
     */
    private function displayCompletionMessage(bool $keepEnv): void
    {
        $this->newLine();
        $this->info('🎉 A2Commerce package uninstalled successfully!');
        $this->newLine();
        
        $this->comment('📋 What was removed:');
        $this->line('   ✅ All A2Commerce copied files and stubs');
        $this->line('   ✅ PayPal webhook route from routes/api.php');
        if (!$keepEnv) {
            $this->line('   ✅ A2Commerce environment variables');
        } else {
            $this->line('   ⚠️  Environment variables preserved (--keep-env)');
        }
        $this->newLine();
        
        $this->comment('📖 Final steps:');
        $this->line('   1. Remove "a2-atu/a2commerce" from your composer.json');
        $this->line('   2. Run: composer remove a2-atu/a2commerce');
        $this->line('   3. Manually remove database tables if needed (migrations are not rolled back)');
        $this->line('   4. Review your application for any remaining A2Commerce references');
        $this->newLine();
        
        if ($keepEnv) {
            $this->warn('⚠️  Note: Environment variables were preserved. Remove them manually if needed.');
            $this->newLine();
        }
        
        $this->info('✨ Thank you for using A2Commerce!');
    }
}

