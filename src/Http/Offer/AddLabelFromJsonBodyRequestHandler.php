<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AddLabelFromJsonBodyRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private DeserializerInterface $labelJsonDeserializer;

    public function __construct(
        CommandBus $commandBus,
        DeserializerInterface $labelJsonDeserializer
    ) {
        $this->commandBus = $commandBus;
        $this->labelJsonDeserializer = $labelJsonDeserializer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $bodyContent = $request->getBody()->getContents();
        try {
            $label = $this->labelJsonDeserializer->deserialize($bodyContent);
        } catch (\InvalidArgumentException $exception) {
            throw ApiProblem::bodyInvalidDataWithDetail('The label should match pattern: ^[^;]{2,255}$');
        }

        $this->commandBus->dispatch(new AddLabel($offerId, $label));
        return new NoContentResponse();
    }
}
