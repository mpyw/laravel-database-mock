<?php

namespace Mpyw\LaravelDatabaseMock;

use Illuminate\Support\ServiceProvider;

class DatabaseMockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseMocker::class);
    }
}
