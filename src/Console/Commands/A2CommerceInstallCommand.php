<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;

class A2CommerceInstallCommand extends Command
{
    protected $signature = 'a2commerce:install {--skip-env : Do not modify .env files} {--no-overwrite : Skip existing files instead of replacing}';

    protected $description = 'Install A2Commerce into the current Laravel project';

    public function handle(Installer $installer): int
    {
        $overwrite = !$this->option('no-overwrite');
        $touchEnv = !$this->option('skip-env');
        $results = $installer->install($overwrite, $touchEnv);

        $this->info('A2Commerce assets copied: ' . count($results['copied']['copied']));
        if ($results['copied']['skipped'] !== []) {
            $this->warn('Skipped existing files: ' . count($results['copied']['skipped']));
        }

        if (!$touchEnv) {
            $this->line('Env keys were not modified (skip-env).');
        } else {
            foreach ($results['env'] as $file => $keys) {
                $this->line(sprintf('Env updated (%s): %s', $file, $keys === [] ? 'none needed' : implode(', ', $keys)));
            }
        }

        $routes = $results['routes'] ?? [];
        if ($routes !== []) {
            $this->line(sprintf('Routes file: %s', $routes['path'] ?? 'routes/api.php'));
            $this->line($routes['added'] ? 'Route block ensured.' : 'Route block already present.');
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}

