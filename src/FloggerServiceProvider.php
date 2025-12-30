<?php

namespace Xoshbin\Flogger;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FloggerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('flogger')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageBooted()
    {
        FilamentAsset::register([
            Css::make('flogger-assets', __DIR__.'/../resources/dist/flogger.css'),
        ], 'xoshbin/flogger');
    }
}
