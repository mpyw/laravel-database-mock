<?php

namespace Mpyw\LaravelDatabaseMock\Proxies;

use Illuminate\Database\ConnectionInterface;
use Mpyw\MockeryPDO\Expectations\ExecuteExpectationProxy;
use Mpyw\MockeryPDO\Expectations\PrepareExpectationProxy;
use Mpyw\MockeryPDO\PDOMockProxy;
use ReflectionProperty;

/**
 * Class SingleConnectionProxy
 *
 * @mixin \Mpyw\MockeryPDO\PDOMockProxy
 */
class SingleConnectionProxy
{
    /**
     * @var \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Mpyw\MockeryPDO\PDOMockProxy
     */
    protected $pdo;

    /**
     * @var bool
     */
    protected $ordered = true;

    /**
     * SingleConnectionProxy constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Mpyw\MockeryPDO\PDOMockProxy            $pdo
     */
    public function __construct(ConnectionInterface $connection, PDOMockProxy $pdo)
    {
        $this->connection = $connection;
        $this->pdo = $pdo;
    }

    /**
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->getPdo()->$name(...$arguments);
    }

    /**
     * @return \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @return \Mpyw\MockeryPDO\PDOMockProxy
     */
    public function getPdo(): PDOMockProxy
    {
        return $this->pdo;
    }

    /**
     * @return $this
     */
    public function inThisOrder()
    {
        $this->ordered = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function inAnyOrder()
    {
        $this->ordered = false;

        return $this;
    }

    /**
     * @param  mixed|string                                          $sql
     * @param  array                                                 $bindings
     * @return \Mpyw\MockeryPDO\Expectations\ExecuteExpectationProxy
     */
    public function shouldSelect($sql, array $bindings = []): ExecuteExpectationProxy
    {
        return $this->shouldPrepareForSelect($sql, $bindings)->shouldExecute();
    }

    /**
     * @param mixed|string $sql
     * @param array        $bindings
     */
    public function shouldInsert($sql, array $bindings = []): void
    {
        $this->shouldRunStatement($sql, $bindings);
    }

    /**
     * @param mixed|string $sql
     * @param array        $bindings
     */
    public function shouldUpdateOne($sql, array $bindings = []): void
    {
        $this->shouldUpdateForRows($sql, $bindings, 1);
    }

    /**
     * @param mixed|string $sql
     * @param array        $bindings
     * @param int          $rowCount
     */
    public function shouldUpdateForRows($sql, array $bindings, int $rowCount): void
    {
        $this->shouldRunAffectingStatementForRows($sql, $bindings, $rowCount);
    }

    /**
     * @param mixed|string $sql
     * @param array        $bindings
     */
    public function shouldDeleteOne($sql, array $bindings): void
    {
        $this->shouldDeleteForRows($sql, $bindings, 1);
    }

    /**
     * @param mixed|string $sql
     * @param array        $bindings
     * @param int          $rowCount
     */
    public function shouldDeleteForRows($sql, array $bindings, int $rowCount): void
    {
        $this->shouldRunAffectingStatementForRows($sql, $bindings, $rowCount);
    }

    /**
     * @param mixed|string $sql
     * @param array        $bindings
     * @param int          $rowCount
     */
    public function shouldRunAffectingStatementForRows($sql, array $bindings, int $rowCount): void
    {
        $this->shouldPrepareForEffect($sql, $bindings)->shouldExecute()->shouldRowCountReturns($rowCount);
    }

    /**
     * @param mixed|string $sql
     * @param array        $bindings
     */
    public function shouldRunStatement($sql, array $bindings = []): void
    {
        $this->shouldPrepareForEffect($sql, $bindings)->shouldExecute();
    }

    /**
     * @param mixed|string $sql
     * @param int          $rowCount
     */
    public function shouldRunUnpreparedStatementForRows($sql, int $rowCount): void
    {
        $this->getPdo()->shouldExec($sql)->andReturn($rowCount);
    }

    /**
     * @param  mixed|string                                          $sql
     * @param  array                                                 $bindings
     * @return \Mpyw\MockeryPDO\Expectations\PrepareExpectationProxy
     */
    public function shouldPrepareForSelect($sql, array $bindings = []): PrepareExpectationProxy
    {
        return $this->shouldPrepare($sql, $bindings, true);
    }

    /**
     * @param  mixed|string                                          $sql
     * @param  array                                                 $bindings
     * @return \Mpyw\MockeryPDO\Expectations\PrepareExpectationProxy
     */
    public function shouldPrepareForEffect($sql, array $bindings = []): PrepareExpectationProxy
    {
        return $this->shouldPrepare($sql, $bindings, false);
    }

    /**
     * @param  mixed|string                                          $sql
     * @param  array                                                 $bindings
     * @param  bool                                                  $setFetchMode
     * @return \Mpyw\MockeryPDO\Expectations\PrepareExpectationProxy
     */
    protected function shouldPrepare($sql, array $bindings, bool $setFetchMode): PrepareExpectationProxy
    {
        $stmt = $this->getPdo()->shouldPrepare($sql);

        if ($setFetchMode) {
            $stmt->shouldReceive('setFetchMode', $this->getFetchMode());
        }

        $this->getConnection()->bindValues(
            new BindValueDelegator($stmt->shouldBind()),
            $this->getConnection()->prepareBindings($bindings)
        );

        if ($this->ordered) {
            $stmt->ordered()->once();
        }

        return $stmt;
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @return int
     */
    public function getFetchMode(): int
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $reflection = new ReflectionProperty($this->getConnection(), 'fetchMode');
        $reflection->setAccessible(true);
        return $reflection->getValue($this->getConnection());
    }
}
