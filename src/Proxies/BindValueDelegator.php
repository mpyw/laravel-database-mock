<?php

namespace Mpyw\LaravelDatabaseMock\Proxies;

use Mpyw\MockeryPDO\States\BindingState;
use PDO;
use PDOStatement;

class BindValueDelegator extends PDOStatement
{
    /**
     * @var \Mpyw\MockeryPDO\States\BindingState
     */
    protected $state;

    /**
     * BindValueDelegator constructor.
     *
     * @param \Mpyw\MockeryPDO\States\BindingState $state
     */
    public function __construct(BindingState $state)
    {
        $this->state = $state;
    }

    /**
     * This method will be invoked by Laravel core.
     *
     * @param  mixed $parameter
     * @param  mixed $value
     * @param  int   $dataType
     * @return bool
     */
    public function bindValue($parameter, $value, $dataType = PDO::PARAM_STR): bool
    {
        $this->state->valueAs($parameter, $value, $dataType);

        return true;
    }
}
