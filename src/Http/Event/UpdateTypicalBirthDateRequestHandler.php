<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalBirthDate;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateTypicalBirthDateRequestHandler implements RequestHandlerInterface
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

        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['typicalBirthDate']) || !is_string($data['typicalBirthDate'])) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError('/typicalBirthDate', 'A valid date string (Y-m-d) is required.')
            );
        }

        $typicalBirthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $data['typicalBirthDate']);

        if ($typicalBirthDate === false || $typicalBirthDate->format('Y-m-d') !== $data['typicalBirthDate']) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError('/typicalBirthDate', 'The value must be a valid date in the format Y-m-d.')
            );
        }

        $this->commandBus->dispatch(
            new UpdateTypicalBirthDate($eventId, $typicalBirthDate)
        );

        return new NoContentResponse();
    }
}
