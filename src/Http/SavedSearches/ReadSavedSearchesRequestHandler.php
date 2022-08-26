<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ReadSavedSearchesRequestHandler implements RequestHandlerInterface
{
    private SavedSearchRepositoryInterface $savedSearchRepository;

    public function __construct(SavedSearchRepositoryInterface $savedSearchRepository)
    {
        $this->savedSearchRepository = $savedSearchRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->savedSearchRepository->ownedByCurrentUser());
    }
}
