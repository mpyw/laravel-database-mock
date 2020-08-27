<?php

namespace Mpyw\LaravelDatabaseMock;

use Illuminate\Database\DatabaseManager;
use Mpyw\LaravelDatabaseMock\Proxies\ConnectionProxies;
use Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy;
use Mpyw\MockeryPDO\MockeryPDO;

class DatabaseMocker
{
    /**
     * @var \Mpyw\MockeryPDO\MockeryPDO
     */
    protected $mocker;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * DatabaseMocker constructor.
     *
     * @param \Mpyw\MockeryPDO\MockeryPDO          $service
     * @param \Illuminate\Database\DatabaseManager $db
     */
    public function __construct(MockeryPDO $service, DatabaseManager $db)
    {
        $this->mocker = $service;
        $this->db = $db;
    }

    /**
     * @param  null|string                                             $connectionName
     * @return \Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy
     */
    public function mockPdo(?string $connectionName = null): SingleConnectionProxy
    {
        $pdo = $this->mocker->mock();
        $connection = $this->db->connection($connectionName);

        $connection->setPdo($pdo);
        $connection->setReadPdo($pdo);

        return new SingleConnectionProxy($connection, $pdo);
    }

    /**
     * @param  null|string                                         $connectionName
     * @return \Mpyw\LaravelDatabaseMock\Proxies\ConnectionProxies
     */
    public function mockEachPdo(?string $connectionName = null): ConnectionProxies
    {
        $readPdo = $this->mocker->mock();
        $writePdo = $this->mocker->mock();

        $connection = $this->db->connection($connectionName);

        $connection->setReadPdo($readPdo);
        $connection->setPdo($writePdo);

        return new ConnectionProxies(
            new SingleConnectionProxy($connection, $readPdo),
            new SingleConnectionProxy($connection, $writePdo)
        );
    }
}
