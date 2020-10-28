<?php

namespace CultuurNet\UDB3\Event\ReadModel;

/**
 * @deprecated Use DocumentDoesNotExistException::gone() instead.
 */
class DocumentGoneException extends \RuntimeException
{
    public function __construct($message = '', $code = 410, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
