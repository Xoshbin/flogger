<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Xoshbin\Flogger\FloggerServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            FloggerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
