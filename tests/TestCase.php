<?php

namespace Ecolabor\SwissUid\Tests;

use Ecolabor\SwissUid\SwissUidServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SwissUidServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        
        // Use test environment for API calls
        config()->set('swiss-uid.environment', 'test');
        config()->set('swiss-uid.cache.enabled', false);
    }
}
