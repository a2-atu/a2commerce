<?php

namespace A2\A2Commerce;

use A2\A2Commerce\Console\Commands\A2CommerceHelpCommand;
use A2\A2Commerce\Console\Commands\A2CommerceInstallCommand;
use A2\A2Commerce\Console\Commands\A2CommerceUninstallCommand;
use A2\A2Commerce\Console\Commands\A2CommerceUpdateCommand;
use A2\A2Commerce\Support\Installer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class A2CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Installer::class, function (Container $app) {
            return new Installer(
                new Filesystem(),
                A2Commerce::stubsPath(),
                $app->basePath()
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                A2CommerceInstallCommand::class,
                A2CommerceUpdateCommand::class,
                A2CommerceUninstallCommand::class,
                A2CommerceHelpCommand::class,
            ]);
        }
    }
}

