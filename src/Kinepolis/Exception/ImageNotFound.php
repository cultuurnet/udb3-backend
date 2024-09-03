<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Exception;

final class ImageNotFound extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
