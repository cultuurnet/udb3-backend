<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\SavedSearches\Command\UpdateSavedSearchJSONDeserializer;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchReadRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateSavedSearchRequestHandler implements RequestHandlerInterface
{
    private string $userId;
    private CommandBus $commandBus;
    private SavedSearchReadRepository $savedSearchReadRepository;

    public function __construct(
        string $userId,
        CommandBus $commandBus,
        SavedSearchReadRepository $savedSearchReadRepository
    ) {
        $this->userId = $userId;
        $this->commandBus = $commandBus;
        $this->savedSearchReadRepository = $savedSearchReadRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $id = $routeParameters->get('id');

        $savedSearch = $this->savedSearchReadRepository->findById($id);
        if ($savedSearch === null) {
            throw ApiProblem::savedSearchNotFound($id);
        }

        if ($savedSearch->getUserId() !== $this->userId) {
            throw ApiProblem::unauthorizedSavedSearch();
        }

        $commandDeserializer = new UpdateSavedSearchJSONDeserializer(
            $this->userId,
            $id
        );

        $command = $commandDeserializer->deserialize($request->getBody()->getContents());

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }
}
