<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\AgeRangeDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use Psr\Http\Message\ServerRequestInterface;

class AgeRangeValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();
        if (!isset($data->typicalAgeRange) || !is_string($data->typicalAgeRange)) {
            return $request;
        }

        $ageRangeDenormalizer = new AgeRangeDenormalizer();
        $from = $ageRangeDenormalizer->denormalizeFrom($data->typicalAgeRange, AgeRange::class);
        $to = $ageRangeDenormalizer->denormalizeTo($data->typicalAgeRange, AgeRange::class);

        if ($from && $to && $from->gt($to)) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/typicalAgeRange',
                    '"From" age should not be greater than the "to" age.'
                )
            );
        }

        return $request;
    }
}
