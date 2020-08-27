<?php

namespace Mpyw\LaravelDatabaseMock\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Mockery;
use Mpyw\LaravelDatabaseMock\DatabaseMockServiceProvider;
use Mpyw\LaravelDatabaseMock\Facades\DBMock;
use Orchestra\Testbench\TestCase as BaseTestCase;

class Test extends BaseTestCase
{
    public function getPackageProviders($app)
    {
        return [DatabaseMockServiceProvider::class];
    }

    /**
     * For testing both Laravel 7.x+ and 6.x-
     *
     * @param  string $date
     * @return string
     */
    protected function formatDate(string $date): string
    {
        return version_compare($this->app->version(), '7', '<')
            ? Carbon::parse($date)->format('Y-m-d H:i:s')
            : $date;
    }

    public function testSelectToFetchAll(): void
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldSelect('select * from `users`')
            ->shouldFetchAllReturns([[
                'id' => 1,
                'name' => 'John',
                'email' => 'john@example.com',
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]]);

        $this->assertEquals([[
            'id' => 1,
            'name' => 'John',
            'email' => 'john@example.com',
            'created_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
            'updated_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
        ]], User::all()->toArray());
    }

    public function testInsert(): void
    {
        Carbon::setTestNow('2020-01-01 00:00:00');

        $pdo = DBMock::mockPdo();
        $pdo->shouldInsert(
            'insert into `users` (`name`, `email`, `updated_at`, `created_at`) values (?, ?, ?, ?)',
            ['John', 'john@example.com', '2020-01-01 00:00:00', '2020-01-01 00:00:00']
        );
        $pdo->expects('lastInsertId')->andReturn(2);

        $user = new User();
        $user->forceFill(['name' => 'John', 'email' => 'john@example.com'])->save();
        $this->assertEquals([
            'id' => 2,
            'name' => 'John',
            'email' => 'john@example.com',
            'created_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
            'updated_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
        ], $user->toArray());
    }

    public function testInsertUsingCallbackMatcher(): void
    {
        Carbon::setTestNow('2020-01-01 00:00:00');

        $pdo = DBMock::mockPdo();
        $pdo->shouldInsert(
            Mockery::on(function (string $sql) {
                $this->assertSame('insert into `users` (`name`, `email`, `updated_at`, `created_at`) values (?, ?, ?, ?)', $sql);
                return true;
            }),
            ['John', 'john@example.com', '2020-01-01 00:00:00', '2020-01-01 00:00:00']
        );
        $pdo->expects('lastInsertId')->andReturn(2);

        $user = new User();
        $user->forceFill(['name' => 'John', 'email' => 'john@example.com'])->save();
        $this->assertEquals([
            'id' => 2,
            'name' => 'John',
            'email' => 'john@example.com',
            'created_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
            'updated_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
        ], $user->toArray());
    }

    public function testUpdate(): void
    {
        Carbon::setTestNow('2020-01-02 00:00:00');

        $pdo = DBMock::mockPdo();
        $pdo->shouldSelect('select * from `users` where `email` = ? limit 1', ['john@example.com'])
            ->shouldFetchAllReturns([[
                'id' => 2,
                'name' => 'John',
                'email' => 'john@example.com',
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]]);
        $pdo->shouldUpdateOne(
            version_compare($this->app->version(), '5.8', '<')
                ? 'update `users` set `email` = ?, `updated_at` = ? where `id` = ?'
                : 'update `users` set `email` = ?, `users`.`updated_at` = ? where `id` = ?',
            ['john-01@example.com', '2020-01-02 00:00:00', 2]
        );

        $user = User::query()->where('email', 'john@example.com')->first();
        $user->forceFill(['email' => 'john-01@example.com'])->save();
        $this->assertEquals([
            'id' => 2,
            'name' => 'John',
            'email' => 'john-01@example.com',
            'created_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
            'updated_at' => $this->formatDate('2020-01-02T00:00:00.000000Z'),
        ], $user->toArray());
    }

    public function testUpdateWithReadReplica(): void
    {
        Carbon::setTestNow('2020-01-02 00:00:00');

        $pdos = DBMock::mockEachPdo();
        $pdos->reader()
            ->shouldSelect('select * from `users` where `email` = ? limit 1', ['john@example.com'])
            ->shouldFetchAllReturns([[
                'id' => 2,
                'name' => 'John',
                'email' => 'john@example.com',
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]]);
        $pdos->writer()
            ->shouldUpdateOne(
                version_compare($this->app->version(), '5.8', '<')
                    ? 'update `users` set `email` = ?, `updated_at` = ? where `id` = ?'
                    : 'update `users` set `email` = ?, `users`.`updated_at` = ? where `id` = ?',
                ['john-01@example.com', '2020-01-02 00:00:00', 2]
            );

        $user = User::query()->where('email', 'john@example.com')->first();
        $user->forceFill(['email' => 'john-01@example.com'])->save();
        $this->assertEquals([
            'id' => 2,
            'name' => 'John',
            'email' => 'john-01@example.com',
            'created_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
            'updated_at' => $this->formatDate('2020-01-02T00:00:00.000000Z'),
        ], $user->toArray());
    }

    public function testUpdateAndSelectInAnyOrder(): void
    {
        Carbon::setTestNow('2020-01-02 00:00:00');

        $pdo = DBMock::mockPdo()->inAnyOrder();
        $pdo->shouldUpdateOne(
            version_compare($this->app->version(), '5.8', '<')
                ? 'update `users` set `email` = ?, `updated_at` = ? where `id` = ?'
                : 'update `users` set `email` = ?, `users`.`updated_at` = ? where `id` = ?',
            ['john-01@example.com', '2020-01-02 00:00:00', 2]
        );
        $pdo->shouldSelect('select * from `users` where `email` = ? limit 1', ['john@example.com'])
            ->shouldFetchAllReturns([[
                'id' => 2,
                'name' => 'John',
                'email' => 'john@example.com',
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]]);

        $user = User::query()->where('email', 'john@example.com')->first();
        $user->forceFill(['email' => 'john-01@example.com'])->save();
        $this->assertEquals([
            'id' => 2,
            'name' => 'John',
            'email' => 'john-01@example.com',
            'created_at' => $this->formatDate('2020-01-01T00:00:00.000000Z'),
            'updated_at' => $this->formatDate('2020-01-02T00:00:00.000000Z'),
        ], $user->toArray());
    }
}
