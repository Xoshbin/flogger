<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Xoshbin\Flogger\FloggerServiceProvider;

class ConfigTest extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            FloggerServiceProvider::class,
        ];
    }

    /** @test */
    public function it_loads_default_config()
    {
        // This confirms that FloggerServiceProvider::configurePackage correctly registers the config
        $config = config('flogger.exclude_files');

        $this->assertNotNull($config, 'Config flogger.exclude_files should not be null');
        $this->assertIsArray($config);
    }
}
