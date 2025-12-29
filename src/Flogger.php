<?php

namespace Xoshbin\Flogger;

use Filament\Contracts\Plugin;
use Filament\Panel;

class Flogger implements Plugin
{
    public function getId(): string
    {
        return 'xoshbin-flogger';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverPages(
                in: __DIR__ . '/Pages',
                for: 'Xoshbin\\Flogger\\Pages'
            );
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
