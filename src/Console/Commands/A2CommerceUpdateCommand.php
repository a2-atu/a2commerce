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
        
        // Step 2: Use Installer's update method to refresh files
        // This ensures files are removed and copied using the same path mapping
        $this->step('Refreshing A2Commerce files and stubs...');
        $results = $installer->update($touchEnv);
        
        // Show detailed output
        $this->displayUpdateResults($results['copied'] ?? []);
        
        // Step 3: Environment variables
        $this->step('Checking environment files...');
        if (!$touchEnv) {
            $this->line('   ⏭️  Environment keys skipped (--skip-env flag used).');
        } else {
            $this->handleEnvResults($results['env'] ?? []);
        }
        
        // Step 4: Routes
        $this->step('Verifying API routes...');
        $this->handleRoutes($results['routes'] ?? []);
        
        // Step 15: Clear caches
        $this->step('Clearing application caches...');
        $this->clearCaches();
        
        $this->displayCompletionMessage();
        
        return self::SUCCESS;
    }
    
    /**
     * Display update results grouped by directory
     */
    private function displayUpdateResults(array $copyResults): void
    {
        $copied = $copyResults['copied'] ?? [];
        $skipped = $copyResults['skipped'] ?? [];

        if (empty($copied) && empty($skipped)) {
            $this->line('   ℹ️  No files to update');
            return;
        }

        // Group files by directory for better output
        $byDirectory = [];
        foreach ($copied as $file) {
            $dir = dirname($file);
            if (!isset($byDirectory[$dir])) {
                $byDirectory[$dir] = [];
            }
            $byDirectory[$dir][] = basename($file);
        }

        foreach ($byDirectory as $dir => $files) {
            $relativeDir = $this->getRelativePath($dir);
            $this->info("   ✅ Refreshed " . count($files) . " file(s) in {$relativeDir}/");
        }

        if (!empty($skipped)) {
            $this->warn("   ⚠️  " . count($skipped) . " file(s) skipped (files don't exist or couldn't be overwritten)");
        }
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
        $filesChecked = [];
        
        foreach ($envResults as $file => $keys) {
            $filesChecked[] = basename($file);
            
            if ($keys !== []) {
                $this->info("   ✅ Added to " . basename($file) . ": " . implode(', ', $keys));
                $envUpdated = true;
            } else {
                $this->line("   ℹ️  " . basename($file) . " already contains A2Commerce keys");
            }
        }

        if (empty($filesChecked)) {
            $this->warn('   ⚠️  No .env or .env.example files found. Environment keys were not added.');
        } elseif (!$envUpdated) {
            $this->info('   ✅ All environment files already contain A2Commerce keys.');
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

