<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Place\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Http\Deserializer\Place\MajorInfoJSONDeserializer;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ValueObjects\StringLiteral\StringLiteral;

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
        $placeId = $routeParameters->getPlaceId();

        $majorInfo = $this->majorInfoDeserializer->deserialize(new StringLiteral((string) $request->getBody()));

        $this->commandBus->dispatch(
            new UpdateMajorInfo(
                $placeId,
                $majorInfo->getTitle(),
                $majorInfo->getType(),
                $majorInfo->getAddress(),
                $majorInfo->getCalendar(),
                $majorInfo->getTheme()
            )
        );

        return new NoContentResponse();
    }
}
