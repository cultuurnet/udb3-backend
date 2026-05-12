<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateBirthdateRange;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\BirthdateRangeDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\InvalidAgeRangeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateBirthdateRangeRequestHandler implements RequestHandlerInterface
{
    private DenormalizerInterface $birthdateRangeDenormalizer;

    public function __construct(
        private readonly CommandBus $commandBus,
        DenormalizerInterface $birthdateRangeDenormalizer = null
    ) {
        $this->birthdateRangeDenormalizer = $birthdateRangeDenormalizer ?? new BirthdateRangeDenormalizer();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT_BIRTHDATE_RANGE_PUT),
        );

        /** @var object $data */
        $data = $parser->parse($request)->getParsedBody();

        try {
            $birthdateRange = $this->birthdateRangeDenormalizer->denormalize(
                ['from' => $data->from, 'to' => $data->to],
                BirthdateRange::class
            );
        } catch (InvalidAgeRangeException $exception) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError('/birthdateRange', $exception->getMessage())
            );
        }

        $this->commandBus->dispatch(new UpdateBirthdateRange($eventId, $birthdateRange));

        return new NoContentResponse();
    }
}
