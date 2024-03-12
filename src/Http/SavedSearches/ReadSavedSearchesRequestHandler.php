<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchesOwnedByCurrentUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ReadSavedSearchesRequestHandler implements RequestHandlerInterface
{
    private SavedSearchesOwnedByCurrentUser $savedSearchRepository;

    public function __construct(SavedSearchesOwnedByCurrentUser $savedSearchRepository)
    {
        $this->savedSearchRepository = $savedSearchRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->savedSearchRepository->ownedByCurrentUser());
    }
}
