<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\A2Commerce;
use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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

        // Use Installer's install method to ensure files are tracked correctly for uninstall.
        // Env files are handled directly in this command (mirroring Vormia behavior).
        $this->step('Copying A2Commerce files and stubs...');
        $results = $installer->install($overwrite, false);

        // Show detailed output grouped by directory
        $this->displayCopyResults($results['copied']);

        // Step 2: Environment variables
        $this->step('Updating environment files...');
        if ($touchEnv) {
            $this->updateEnvFiles();
        } else {
            $this->line('   ⏭️  Environment keys skipped (--skip-env flag used).');
        }

        // Step 3: Routes
        $this->step('Ensuring API routes...');
        $this->handleRoutes($results['routes'] ?? []);

        // Step 4: Migrations
        $migrationsRun = $this->handleMigrations();

        $this->displayCompletionMessage($touchEnv, $migrationsRun);

        return self::SUCCESS;
    }

    /**
     * Display copy results grouped by directory
     */
    private function displayCopyResults(array $copyResults): void
    {
        $copied = $copyResults['copied'] ?? [];
        $skipped = $copyResults['skipped'] ?? [];

        if (empty($copied) && empty($skipped)) {
            $this->line('   ℹ️  No files to copy');
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
            $this->info("   ✅ Copied " . count($files) . " file(s) to {$relativeDir}/");
        }

        if (!empty($skipped)) {
            $this->warn("   ⚠️  " . count($skipped) . " existing file(s) skipped (use --no-overwrite to keep existing files)");
        }
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
     * Update .env and .env.example files with A2 configuration
     */
    private function updateEnvFiles(): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        $envBlock = "\n# A2 CONFIGURATION\n"
            . "A2_PAYPAL_MODE=sandbox\n"
            . "\n"
            . "A2_PAYPAL_SECRET=\n"
            . "A2_PAYPAL_CLIENT_ID=\n"
            . "A2_PAYPAL_WEBHOOK_ID=\n"
            . "\n"
            . "A2_ORDER_PREFIX=\"SP-OD\"\n"
            . "A2_TAX_RATE=0\n"
            . "A2_SHIPPING_FEE=0\n"
            . "\n"
            . "A2_CURRENCY=USD\n"
            . "A2_CURRENCY_SYMBOL=\"$\"\n"
            . "A2_CURRENCY_CONVERSION_RATE=1\n";

        // Update .env
        if (File::exists($envPath)) {
            $content = File::get($envPath);
            if (strpos($content, 'A2_PAYPAL_MODE') === false) {
                File::append($envPath, $envBlock);
            }
        }

        // Update .env.example
        if (File::exists($envExamplePath)) {
            $content = File::get($envExamplePath);
            if (strpos($content, 'A2_PAYPAL_MODE') === false) {
                File::append($envExamplePath, $envBlock);
            }
        }

        $this->info('   ✅ Environment files updated successfully (A2 configuration).');
    }

    /**
     * Handle routes results
     */
    private function handleRoutes(array $routes): void
    {
        if ($routes === []) {
            return;
        }

        if ($routes['skipped'] ?? false) {
            $this->warn('   ⚠️  routes/api.php not found. PayPal webhook route was not added.');
            $this->line('   Create routes/api.php first, then re-run the installer to add the webhook route.');
            return;
        }

        if ($routes['added'] ?? false) {
            $this->info('   ✅ PayPal webhook route added to routes/api.php');
        } else {
            $this->info('   ✅ PayPal webhook route already exists in routes/api.php');
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
     * Handle migrations prompt and execution
     */
    private function handleMigrations(): bool
    {
        $this->step('Running database migrations...');

        if (!$this->confirm('Would you like to run migrations now?', true)) {
            $this->line('   ⏭️  Migrations skipped. You can run them later with: php artisan migrate');
            return false;
        }

        return $this->runMigrations();
    }

    /**
     * Run database migrations
     */
    private function runMigrations(): bool
    {
        try {
            $this->line('   Running migrations...');
            $exitCode = Artisan::call('migrate', [], $this->getOutput());

            // Display any output from the migrate command
            $output = Artisan::output();
            if (!empty(trim($output))) {
                $this->line($output);
            }

            if ($exitCode === 0) {
                $this->info('   ✅ Migrations completed successfully!');
                return true;
            } else {
                $this->error('   ❌ Migrations completed with errors (exit code: ' . $exitCode . ')');
                $this->warn('   ⚠️  You can run migrations manually later with: php artisan migrate');
                return false;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Migration failed: ' . $e->getMessage());
            $this->warn('   ⚠️  You can run migrations manually later with: php artisan migrate');
            return false;
        }
    }

    /**
     * Display completion message with next steps
     */
    private function displayCompletionMessage(bool $envTouched, bool $migrationsRun): void
    {
        $this->newLine();
        $this->info('🎉 A2Commerce package installed successfully!');
        $this->newLine();

        $this->comment('📋 Next steps:');
        $this->line('   1. Review and configure your .env file with PayPal credentials:');
        $this->line('   2. Configure commerce settings:');
        $this->line('   3. Verify the PayPal webhook route in routes/api.php');

        if (!$migrationsRun) {
            $this->line('   4. Run migrations: php artisan migrate');
            $this->line('   5. Review the implementation guide: packageflow-md/0-a_2_commerce_implementation_guide.md');
        } else {
            $this->line('   4. Review the implementation guide: packageflow-md/0-a_2_commerce_implementation_guide.md');
        }

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
