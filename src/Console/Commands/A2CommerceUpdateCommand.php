<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\A2Commerce;
use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class A2CommerceUpdateCommand extends Command
{
    protected $signature = 'a2commerce:update {--skip-env : Do not modify .env files} {--force : Skip confirmation prompts}';

    protected $description = 'Update A2Commerce package files (removes old files and copies fresh ones)';

    public function handle(Installer $installer): int
    {
        $this->displayHeader();
        
        $force = $this->option('force');
        $touchEnv = !$this->option('skip-env');
        
        $this->warn('⚠️  WARNING: This will replace existing A2Commerce files with fresh copies.');
        $this->warn('   Make sure you have backed up any custom modifications.');
        $this->newLine();
        
        if (!$force && !$this->confirm('Do you want to continue with the update?', false)) {
            $this->info('❌ Update cancelled.');
            return self::SUCCESS;
        }
        
        // Step 1: Create backup
        $this->step('Creating backup of existing files...');
        $this->createBackup();
        
        // Step 2: Remove old files
        $this->step('Removing old A2Commerce files...');
        $this->removeOldFiles();
        
        // Step 3: Copy fresh Services
        $this->step('Copying Services...');
        $this->copyDirectory('services', app_path('Services'));
        
        // Step 4: Copy fresh Controllers
        $this->step('Copying Controllers...');
        $this->copyDirectory('controllers', app_path('Http/Controllers'));
        
        // Step 5: Copy fresh Models
        $this->step('Copying Models...');
        $this->copyDirectory('models', app_path('Models'));
        
        // Step 6: Copy fresh Events
        $this->step('Copying Events...');
        $this->copyDirectory('events', app_path('Events'));
        
        // Step 7: Copy fresh Listeners
        $this->step('Copying Listeners...');
        $this->copyDirectory('listeners', app_path('Listeners'));
        
        // Step 8: Copy fresh Jobs
        $this->step('Copying Jobs...');
        $this->copyDirectory('jobs', app_path('Jobs'));
        
        // Step 9: Copy fresh Notifications
        $this->step('Copying Notifications...');
        $this->copyDirectory('notifications', app_path('Notifications'));
        
        // Step 10: Copy fresh Migrations
        $this->step('Copying Migrations...');
        $this->copyDirectory('migrations', database_path('migrations'));
        
        // Step 11: Copy fresh Config
        $this->step('Copying Configuration...');
        $this->copyDirectory('config', config_path());
        
        // Step 12: Copy fresh Views
        $this->step('Copying Views...');
        $this->copyDirectory('resources', resource_path());
        
        // Step 13: Environment variables
        $this->step('Checking environment files...');
        if (!$touchEnv) {
            $this->line('   ⏭️  Environment keys skipped (--skip-env flag used).');
        } else {
            $envResults = $installer->ensureEnvKeys();
            $this->handleEnvResults($envResults);
        }
        
        // Step 14: Routes
        $this->step('Verifying API routes...');
        $routes = $installer->ensureRoutes();
        $this->handleRoutes($routes);
        
        // Step 15: Clear caches
        $this->step('Clearing application caches...');
        $this->clearCaches();
        
        $this->displayCompletionMessage();
        
        return self::SUCCESS;
    }
    
    /**
     * Copy a directory from stubs to destination
     */
    private function copyDirectory(string $stubDir, string $destination): void
    {
        $stubsPath = A2Commerce::stubsPath($stubDir);
        
        if (!File::exists($stubsPath)) {
            $this->line("   ⚠️  Source directory not found: {$stubDir}");
            return;
        }
        
        if (!File::isDirectory($stubsPath)) {
            $this->line("   ⚠️  Source is not a directory: {$stubDir}");
            return;
        }
        
        $files = File::allFiles($stubsPath);
        $copied = 0;
        
        foreach ($files as $file) {
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }
            
            $relativePath = ltrim(str_replace($stubsPath, '', $file->getPathname()), '/\\');
            $targetPath = rtrim($destination, '/\\') . '/' . $relativePath;
            
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getPathname(), $targetPath);
            $copied++;
        }
        
        if ($copied > 0) {
            $this->info("   ✅ {$copied} file(s) copied to " . $this->getRelativePath($destination));
        } else {
            $this->line("   ℹ️  No files to copy");
        }
    }
    
    /**
     * Remove old A2Commerce files
     */
    private function removeOldFiles(): void
    {
        $directoriesToRemove = [
            app_path('Services/A2'),
            app_path('Http/Controllers/A2'),
            app_path('Models/A2'),
            app_path('Events/A2'),
            app_path('Listeners/A2'),
            app_path('Jobs/A2'),
            app_path('Notifications/A2'),
            resource_path('views/emails/a2'),
        ];
        
        foreach ($directoriesToRemove as $directory) {
            if (File::exists($directory)) {
                File::deleteDirectory($directory);
                $this->line("   ✅ Removed: " . $this->getRelativePath($directory));
            }
        }
        
        // Remove old migration files
        $migrationPath = database_path('migrations');
        if (File::isDirectory($migrationPath)) {
            foreach (File::files($migrationPath) as $file) {
                if (str_contains($file->getFilename(), 'a2_ec_')) {
                    File::delete($file->getPathname());
                    $this->line("   ✅ Removed migration: " . $file->getFilename());
                }
            }
        }
        
        // Remove config file
        $configPath = config_path('a2_commerce.php');
        if (File::exists($configPath)) {
            File::delete($configPath);
            $this->line("   ✅ Removed config: " . $this->getRelativePath($configPath));
        }
        
        $this->info('   ✅ Old files removed successfully.');
    }
    
    /**
     * Create backup of existing files
     */
    private function createBackup(): void
    {
        $backupDir = storage_path('app/a2commerce-backups/' . date('Y-m-d-H-i-s'));
        
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
        
        $this->info("   ✅ Backup created in: " . $this->getRelativePath($backupDir));
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
        $envUpdated = false;
        foreach ($envResults as $file => $keys) {
            if ($keys !== []) {
                $this->info("   ✅ Added to " . basename($file) . ": " . implode(', ', $keys));
                $envUpdated = true;
            }
        }
        
        if (!$envUpdated) {
            $this->info('   ✅ Environment files already contain all A2Commerce keys.');
        }
    }
    
    /**
     * Handle routes results
     */
    private function handleRoutes(array $routes): void
    {
        if ($routes !== []) {
            if ($routes['added'] ?? false) {
                $this->info('   ✅ PayPal webhook route added to routes/api.php');
            } else {
                $this->info('   ✅ PayPal webhook route already exists in routes/api.php');
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
        $this->line('   ✅ All package files replaced with fresh copies');
        $this->line('   ✅ Configuration files updated');
        $this->line('   ✅ Backups created in storage/app/a2commerce-backups/');
        $this->line('   ✅ Application caches cleared');
        $this->newLine();
        
        $this->comment('📖 Next steps:');
        $this->line('   1. Review any custom modifications in your backup files');
        $this->line('   2. Test your application to ensure everything works correctly');
        $this->line('   3. Run migrations if there are any new ones: php artisan migrate');
        $this->newLine();
        
        $this->info('✨ Your A2Commerce package is now up to date!');
    }
}

