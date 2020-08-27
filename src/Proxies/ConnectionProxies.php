<?php

namespace Mpyw\LaravelDatabaseMock\Proxies;

class ConnectionProxies
{
    /**
     * @var \Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy
     */
    protected $reader;

    /**
     * @var \Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy
     */
    protected $writer;

    /**
     * ConnectionProxies constructor.
     *
     * @param \Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy $reader
     * @param \Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy $writer
     */
    public function __construct(SingleConnectionProxy $reader, SingleConnectionProxy $writer)
    {
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * @return \Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy
     */
    public function reader(): SingleConnectionProxy
    {
        return $this->reader;
    }

    /**
     * @return \Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy
     */
    public function writer(): SingleConnectionProxy
    {
        return $this->writer;
    }
}
