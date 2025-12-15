<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;

class A2CommerceUninstallCommand extends Command
{
    protected $signature = 'a2commerce:uninstall {--keep-env : Leave env keys untouched} {--force : Skip confirmation prompt}';

    protected $description = 'Remove A2Commerce files and env keys from the project';

    public function handle(Installer $installer): int
    {
        if (!$this->option('force') && !$this->confirm('This will delete A2Commerce files. Continue?')) {
            $this->warn('Uninstall aborted.');
            return self::SUCCESS;
        }

        $touchEnv = !$this->option('keep-env');
        $results = $installer->uninstall($touchEnv);

        $this->info('Removed files: ' . count($results['removed']));
        if (!$touchEnv) {
            $this->line('Env keys were left untouched (keep-env).');
        } else {
            foreach ($results['env'] as $file => $keys) {
                $this->line(sprintf('Env cleaned (%s): %s', $file, $keys === [] ? 'none' : implode(', ', $keys)));
            }
        }

        $this->info('Uninstall complete.');

        return self::SUCCESS;
    }
}

