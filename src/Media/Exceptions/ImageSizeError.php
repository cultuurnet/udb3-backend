<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Exceptions;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use Exception;

final class ImageSizeError extends Exception implements ConvertsToApiProblem
{
    public function toApiProblem(): ApiProblem
    {
        throw ApiProblem::fileInvalidSize($this->getMessage());
    }
}
