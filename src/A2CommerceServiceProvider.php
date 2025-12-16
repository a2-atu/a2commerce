<?php

namespace A2\A2Commerce;

use A2\A2Commerce\A2Commerce;
use A2\A2Commerce\Console\Commands\A2CommerceHelpCommand;
use A2\A2Commerce\Console\Commands\A2CommerceInstallCommand;
use A2\A2Commerce\Console\Commands\A2CommerceUninstallCommand;
use A2\A2Commerce\Console\Commands\A2CommerceUpdateCommand;
use A2\A2Commerce\Providers\A2CommerceEventServiceProvider;
use A2\A2Commerce\Support\Installer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class A2CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('a2commerce.version', A2Commerce::VERSION);

        $this->app->singleton(Installer::class, function (Application $app) {
            return new Installer(
                new Filesystem(),
                A2Commerce::stubsPath(),
                $app->basePath()
            );
        });

        // Register the EventServiceProvider to automatically register event listeners
        $this->app->register(A2CommerceEventServiceProvider::class);
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
