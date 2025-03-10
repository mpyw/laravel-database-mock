# Laravel Database Mock [![Build Status](https://github.com/mpyw/laravel-database-mock/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/mpyw/laravel-database-mock/actions) [![Coverage Status](https://coveralls.io/repos/github/mpyw/laravel-database-mock/badge.svg?branch=master)](https://coveralls.io/github/mpyw/laravel-database-mock?branch=master)

> [!WARNING]
> **Experimental**

Database Mocking Library which mocks `PDO` underlying Laravel Connection classes.

## Requirements

- PHP: `^8.2`
- Laravel: `^11.0 || ^12.0`
- Mockery: `^1.6.12`
- [mpyw/mockery-pdo](https://github.com/mpyw/mockery-pdo): `alpha`

## Installing

```bash
composer require mpyw/laravel-database-mock:VERSION@alpha
```

## Example

### SELECT

```php
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
    'created_at' => '2020-01-01T00:00:00.000000Z',
    'updated_at' => '2020-01-01T00:00:00.000000Z',
]], User::all()->toArray());
```

### INSERT

```php
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
    'created_at' => '2020-01-01T00:00:00.000000Z',
    'updated_at' => '2020-01-01T00:00:00.000000Z',
], $user->toArray());
```

### UPDATE

#### Basic

```php
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
    'update `users` set `email` = ?, `users`.`updated_at` = ? where `id` = ?',
    ['john-01@example.com', '2020-01-02 00:00:00', 2]
);

$user = User::query()->where('email', 'john@example.com')->first();
$user->forceFill(['email' => 'john-01@example.com'])->save();
$this->assertEquals([
    'id' => 2,
    'name' => 'John',
    'email' => 'john-01@example.com',
    'created_at' => '2020-01-01T00:00:00.000000Z',
    'updated_at' => '2020-01-02T00:00:00.000000Z',
], $user->toArray());
```

#### Using Read Replica

```php
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
        'update `users` set `email` = ?, `users`.`updated_at` = ? where `id` = ?',
        ['john-01@example.com', '2020-01-02 00:00:00', 2]
    );

$user = User::query()->where('email', 'john@example.com')->first();
$user->forceFill(['email' => 'john-01@example.com'])->save();
$this->assertEquals([
    'id' => 2,
    'name' => 'John',
    'email' => 'john-01@example.com',
    'created_at' => '2020-01-01T00:00:00.000000Z',
    'updated_at' => '2020-01-02T00:00:00.000000Z',
], $user->toArray());
```
