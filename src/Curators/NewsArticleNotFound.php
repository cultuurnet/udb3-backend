<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Exception;

final class NewsArticleNotFound extends Exception
{
    public function __construct(UUID $id)
    {
        parent::__construct('News article with id "' . $id->toString() . '" was not found.');
    }
}
