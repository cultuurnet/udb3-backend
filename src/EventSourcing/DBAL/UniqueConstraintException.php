<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

class UniqueConstraintException extends \Exception
{
    /**
     * UniqueConstraintException constructor.
     * @param string $uuid
     * @param string $unique
     */
    public function __construct($uuid, $unique)
    {
        $message = 'Not unique: uuid = ' . $uuid . ', unique value = ' . $unique;
        parent::__construct($message);
    }
}
