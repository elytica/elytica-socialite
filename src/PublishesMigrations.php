<?php

declare(strict_types=1);

namespace Elytica\Socialite;

use Illuminate\Support\Str;

trait PublishesMigrations
{
    protected function registerMigrations(string $directory): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $files = $this->app->make('files')->allFiles($directory);
        $map = [];
        $offset = 0;

        foreach ($files as $file) {
            $timestamp = now()->addSeconds($offset)->format('Y_m_d_His');
            $map[$file->getPathname()] = $this->app->databasePath(
                'migrations/' . $timestamp . Str::after($file->getFilename(), '00_00_00_000000')
            );
            $offset++;
        }

        $this->publishes($map, 'migrations');
    }
}
