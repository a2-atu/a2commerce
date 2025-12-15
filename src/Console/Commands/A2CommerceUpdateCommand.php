<?php

namespace A2\A2Commerce\Console\Commands;

use A2\A2Commerce\Support\Installer;
use Illuminate\Console\Command;

class A2CommerceUpdateCommand extends Command
{
    protected $signature = 'a2commerce:update {--skip-env : Do not modify .env files}';

    protected $description = 'Update A2Commerce stubs and env keys';

    public function handle(Installer $installer): int
    {
        $touchEnv = !$this->option('skip-env');
        $results = $installer->update($touchEnv);

        $this->info('A2Commerce assets refreshed: ' . count($results['copied']['copied']));
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

        $this->info('Update complete.');

        return self::SUCCESS;
    }
}

