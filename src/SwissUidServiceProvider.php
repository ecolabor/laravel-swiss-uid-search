<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Ecolabor\SwissUid\Contracts\UidClientInterface;
use Ecolabor\SwissUid\Services\UidClient;

/**
 * Swiss UID Service Provider
 *
 * Provides integration with the Swiss UID Webservice Version 5.0.
 *
 * @see https://www.bfs.admin.ch/bfs/de/home/register/unternehmensregister/unternehmens-identifikationsnummer.html
 */
class SwissUidServiceProvider extends PackageServiceProvider
{
    public static string $name = 'swiss-uid';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('ecolabor/laravel-swiss-uid-search');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(UidClientInterface::class, function () {
            return new UidClient();
        });

        $this->app->alias(UidClientInterface::class, 'swiss-uid');
    }

    public function packageBooted(): void
    {
        //
    }
}
