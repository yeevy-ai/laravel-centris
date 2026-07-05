<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Yeevy\LaravelCentris\CentrisServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            CentrisServiceProvider::class,
        ];
    }
}
