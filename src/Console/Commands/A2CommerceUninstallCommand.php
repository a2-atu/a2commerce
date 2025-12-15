<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\A2Commerce;
use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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
        
        // Step 1: Create backup
        $this->step('Creating final backup...');
        $this->createFinalBackup();
        
        // Step 2: Remove Services
        $this->step('Removing Services...');
        $this->removeDirectory(app_path('Services/A2'));
        
        // Step 3: Remove Controllers
        $this->step('Removing Controllers...');
        $this->removeDirectory(app_path('Http/Controllers/A2'));
        
        // Step 4: Remove Models
        $this->step('Removing Models...');
        $this->removeDirectory(app_path('Models/A2'));
        
        // Step 5: Remove Events
        $this->step('Removing Events...');
        $this->removeDirectory(app_path('Events/A2'));
        
        // Step 6: Remove Listeners
        $this->step('Removing Listeners...');
        $this->removeDirectory(app_path('Listeners/A2'));
        
        // Step 7: Remove Jobs
        $this->step('Removing Jobs...');
        $this->removeDirectory(app_path('Jobs/A2'));
        
        // Step 8: Remove Notifications
        $this->step('Removing Notifications...');
        $this->removeDirectory(app_path('Notifications/A2'));
        
        // Step 9: Remove Migrations
        $this->step('Removing Migrations...');
        $this->removeMigrations();
        
        // Step 10: Remove Config
        $this->step('Removing Configuration...');
        $this->removeFile(config_path('a2_commerce.php'));
        
        // Step 11: Remove Views
        $this->step('Removing Views...');
        $this->removeDirectory(resource_path('views/emails/a2'));
        
        // Step 12: Environment variables
        $this->step('Cleaning up environment files...');
        $touchEnv = !$keepEnv;
        if ($keepEnv) {
            $this->line('   ⏭️  Environment keys preserved (--keep-env flag used).');
        } else {
            $envResults = $installer->removeEnvKeys();
            $this->handleEnvResults($envResults);
        }
        
        // Step 13: Routes
        $this->step('Removing API routes...');
        $routes = $installer->removeRoutes();
        $this->handleRoutes($routes);
        
        // Step 14: Clear caches
        $this->step('Clearing application caches...');
        $this->clearCaches();
        
        $this->displayCompletionMessage($keepEnv);
        
        return self::SUCCESS;
    }
    
    /**
     * Remove a directory
     */
    private function removeDirectory(string $path): void
    {
        if (!File::exists($path)) {
            $this->line("   ℹ️  Directory does not exist: " . $this->getRelativePath($path));
            return;
        }
        
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
            $this->info("   ✅ Removed directory: " . $this->getRelativePath($path));
        } else {
            File::delete($path);
            $this->info("   ✅ Removed file: " . $this->getRelativePath($path));
        }
    }
    
    /**
     * Remove a file
     */
    private function removeFile(string $path): void
    {
        if (!File::exists($path)) {
            $this->line("   ℹ️  File does not exist: " . $this->getRelativePath($path));
            return;
        }
        
        File::delete($path);
        $this->info("   ✅ Removed file: " . $this->getRelativePath($path));
    }
    
    /**
     * Remove migration files
     */
    private function removeMigrations(): void
    {
        $migrationPath = database_path('migrations');
        if (!File::isDirectory($migrationPath)) {
            $this->line("   ℹ️  Migrations directory does not exist");
            return;
        }
        
        $removed = 0;
        foreach (File::files($migrationPath) as $file) {
            if (str_contains($file->getFilename(), 'a2_ec_')) {
                File::delete($file->getPathname());
                $this->line("   ✅ Removed migration: " . $file->getFilename());
                $removed++;
            }
        }
        
        if ($removed === 0) {
            $this->line("   ℹ️  No A2Commerce migrations found to remove");
        }
    }
    
    /**
     * Get relative path from base path for display
     */
    private function getRelativePath(string $absolutePath): string
    {
        $basePath = base_path();
        if (str_starts_with($absolutePath, $basePath)) {
            return ltrim(str_replace($basePath, '', $absolutePath), '/\\');
        }
        return $absolutePath;
    }
    
    /**
     * Handle environment file results
     */
    private function handleEnvResults(array $envResults): void
    {
        $envCleaned = false;
        foreach ($envResults as $file => $keys) {
            if ($keys !== []) {
                $this->info("   ✅ Removed from " . basename($file) . ": " . implode(', ', $keys));
                $envCleaned = true;
            }
        }
        
        if (!$envCleaned) {
            $this->info('   ✅ No A2Commerce environment keys found to remove.');
        }
    }
    
    /**
     * Handle routes results
     */
    private function handleRoutes(array $routes): void
    {
        if ($routes !== []) {
            if ($routes['removed'] ?? false) {
                $this->info('   ✅ PayPal webhook route removed from routes/api.php');
            } else {
                $this->info('   ✅ No route block found to remove.');
            }
        }
    }
    
    /**
     * Clear application caches
     */
    private function clearCaches(): void
    {
        $cacheCommands = [
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
            'cache:clear' => 'Application cache',
        ];
        
        foreach ($cacheCommands as $command => $description) {
            try {
                \Illuminate\Support\Facades\Artisan::call($command);
                $this->line("   ✅ Cleared: {$description}");
            } catch (\Exception $e) {
                $this->line("   ⚠️  Skipped: {$description} (not available)");
            }
        }
    }
    
    /**
     * Create final backup before uninstallation
     */
    private function createFinalBackup(): void
    {
        $backupDir = storage_path('app/a2commerce-final-backup-' . date('Y-m-d-H-i-s'));
        
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        $filesToBackup = [
            app_path('Services/A2') => $backupDir . '/Services/A2',
            app_path('Http/Controllers/A2') => $backupDir . '/Http/Controllers/A2',
            app_path('Models/A2') => $backupDir . '/Models/A2',
            app_path('Events/A2') => $backupDir . '/Events/A2',
            app_path('Listeners/A2') => $backupDir . '/Listeners/A2',
            app_path('Jobs/A2') => $backupDir . '/Jobs/A2',
            app_path('Notifications/A2') => $backupDir . '/Notifications/A2',
            config_path('a2_commerce.php') => $backupDir . '/config/a2_commerce.php',
            resource_path('views/emails/a2') => $backupDir . '/views/emails/a2',
            base_path('routes/api.php') => $backupDir . '/routes/api.php',
            base_path('.env') => $backupDir . '/.env',
        ];
        
        foreach ($filesToBackup as $source => $destination) {
            if (File::exists($source)) {
                if (File::isDirectory($source)) {
                    File::copyDirectory($source, $destination);
                } else {
                    File::ensureDirectoryExists(dirname($destination));
                    File::copy($source, $destination);
                }
            }
        }
        
        $this->info("   ✅ Final backup created in: " . $this->getRelativePath($backupDir));
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
        $this->line('   ✅ Application caches cleared');
        $this->line('   ✅ Final backup created in storage/app/');
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

