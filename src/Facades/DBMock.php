<?php

namespace Mpyw\LaravelDatabaseMock\Facades;

use Illuminate\Support\Facades\Facade;
use Mpyw\LaravelDatabaseMock\DatabaseMocker;

/**
 * Class DBMock
 *
 * @method static \Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy mockPdo(?string $connectionName = null):
 * @method static \Mpyw\LaravelDatabaseMock\Proxies\ConnectionProxies mockEachPdo(?string $connectionName = null):
 * @see \Mpyw\LaravelDatabaseMock\DatabaseMocker
 */
class DBMock extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return DatabaseMocker::class;
    }
}
