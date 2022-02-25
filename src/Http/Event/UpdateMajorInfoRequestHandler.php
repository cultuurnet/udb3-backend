<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Http\Deserializer\Event\MajorInfoJSONDeserializer;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use CultuurNet\UDB3\StringLiteral;

class UpdateMajorInfoRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private MajorInfoJSONDeserializer $majorInfoDeserializer;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;

        $this->majorInfoDeserializer = new MajorInfoJSONDeserializer();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $majorInfo = $this->majorInfoDeserializer->deserialize(new StringLiteral((string) $request->getBody()));

        $this->commandBus->dispatch(
            new UpdateMajorInfo(
                $eventId,
                $majorInfo->getTitle(),
                $majorInfo->getType(),
                $majorInfo->getLocation(),
                $majorInfo->getCalendar(),
                $majorInfo->getTheme()
            )
        );

        return new NoContentResponse();
    }
}
