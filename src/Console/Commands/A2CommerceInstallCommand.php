<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\A2Commerce;
use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class A2CommerceInstallCommand extends Command
{
    protected $signature = 'a2commerce:install {--skip-env : Do not modify .env files} {--no-overwrite : Skip existing files instead of replacing}';

    protected $description = 'Install A2Commerce package with all necessary files and configurations';

    public function handle(Installer $installer): int
    {
        $this->displayHeader();

        $overwrite = !$this->option('no-overwrite');
        $touchEnv = !$this->option('skip-env');

        // Step 1: Copy services
        $this->step('Copying Services...');
        $this->copyDirectory('services', app_path('Services'));

        // Step 2: Copy controllers
        $this->step('Copying Controllers...');
        $this->copyDirectory('controllers', app_path('Http/Controllers'));

        // Step 3: Copy models
        $this->step('Copying Models...');
        $this->copyDirectory('models', app_path('Models'));

        // Step 4: Copy events
        $this->step('Copying Events...');
        $this->copyDirectory('events', app_path('Events'));

        // Step 5: Copy listeners
        $this->step('Copying Listeners...');
        $this->copyDirectory('listeners', app_path('Listeners'));

        // Step 6: Copy jobs
        $this->step('Copying Jobs...');
        $this->copyDirectory('jobs', app_path('Jobs'));

        // Step 7: Copy notifications
        $this->step('Copying Notifications...');
        $this->copyDirectory('notifications', app_path('Notifications'));

        // Step 8: Copy migrations
        $this->step('Copying Migrations...');
        $this->copyDirectory('migrations', database_path('migrations'));

        // Step 9: Copy config
        $this->step('Copying Configuration...');
        $this->copyDirectory('config', config_path());

        // Step 10: Copy resources/views
        $this->step('Copying Views...');
        $this->copyDirectory('resources', resource_path());

        // Step 11: Environment variables
        $this->step('Updating environment files...');
        $envResults = $touchEnv ? $installer->ensureEnvKeys() : [];
        $this->handleEnvResults($envResults, $touchEnv);

        // Step 12: Routes
        $this->step('Ensuring API routes...');
        $routes = $installer->ensureRoutes();
        $this->handleRoutes($routes);

        $this->displayCompletionMessage($touchEnv);

        return self::SUCCESS;
    }

    /**
     * Copy a directory from stubs to destination
     */
    private function copyDirectory(string $stubDir, string $destination): void
    {
        $stubsPath = A2Commerce::stubsPath($stubDir);
        $overwrite = !$this->option('no-overwrite');

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
        $skipped = 0;

        foreach ($files as $file) {
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            $relativePath = ltrim(str_replace($stubsPath, '', $file->getPathname()), '/\\');
            $targetPath = rtrim($destination, '/\\') . '/' . $relativePath;

            if (File::exists($targetPath) && !$overwrite) {
                $skipped++;
                continue;
            }

            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getPathname(), $targetPath);
            $copied++;
        }

        if ($copied > 0) {
            $this->info("   ✅ {$copied} file(s) copied to " . $this->getRelativePath($destination));
        }

        if ($skipped > 0) {
            $this->warn("   ⚠️  {$skipped} existing file(s) skipped (use --no-overwrite to keep existing files)");
        }

        if ($copied === 0 && $skipped === 0) {
            $this->line("   ℹ️  No files to copy");
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
    private function handleEnvResults(array $envResults, bool $touchEnv): void
    {
        if (!$touchEnv) {
            $this->line('   ⏭️  Environment keys skipped (--skip-env flag used).');
            return;
        }

        $envUpdated = false;
        foreach ($envResults as $file => $keys) {
            if ($keys !== []) {
                $this->info("   ✅ Added to " . basename($file) . ": " . implode(', ', $keys));
                $envUpdated = true;
            }
        }

        if (!$envUpdated) {
            $this->info('   ✅ Environment files already contain A2Commerce keys.');
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
     * Display the header
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('🚀 Installing A2Commerce Package...');
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
     * Display completion message with next steps
     */
    private function displayCompletionMessage(bool $envTouched): void
    {
        $this->newLine();
        $this->info('🎉 A2Commerce package installed successfully!');
        $this->newLine();

        $this->comment('📋 Next steps:');
        $this->line('   1. Review and configure your .env file with PayPal credentials:');
        $this->line('   2. Configure commerce settings:');
        $this->line('   3. Verify the PayPal webhook route in routes/api.php');
        $this->line('   4. Run migrations: php artisan migrate');
        $this->line('   5. Review the implementation guide: packageflow-md/0-a_2_commerce_implementation_guide.md');
        $this->newLine();

        if (!$envTouched) {
            $this->warn('⚠️  Note: Environment keys were not modified (--skip-env flag used).');
            $this->line('   Run: php artisan a2commerce:help to see required env keys.');
            $this->newLine();
        }

        $this->comment('📖 For help and available commands, run: php artisan a2commerce:help');
        $this->newLine();

        $this->info('✨ Happy coding with A2Commerce!');
    }
}
