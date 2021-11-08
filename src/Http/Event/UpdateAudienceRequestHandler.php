<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\IncompatibleAudienceType;
use CultuurNet\UDB3\Event\Serializer\UpdateAudienceDenormalizer;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateAudienceRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT_AUDIENCE),
            new DenormalizingRequestBodyParser(new UpdateAudienceDenormalizer($eventId), UpdateAudienceDenormalizer::class)
        );

        /** @var UpdateAudience $updateAudience */
        $updateAudience = $parser->parse($request)->getParsedBody();

        try {
            $this->commandBus->dispatch($updateAudience);
        } catch (IncompatibleAudienceType $incompatibleAudienceType) {
            throw ApiProblem::inCompatibleAudienceType(
                'The audience type "' . $updateAudience->getAudience()->getAudienceType()->getName() . '" can not be set.'
            );
        }

        return new NoContentResponse();
    }
}
