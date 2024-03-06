<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Ownership\Serializers\RequestOwnershipDenormalizer;
use CultuurNet\UDB3\User\CurrentUser;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\UuidFactoryInterface;

final class RequestOwnershipRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private UuidFactoryInterface $uuidFactory;
    private CurrentUser $currentUser;
    private OwnershipSearchRepository $ownershipSearchRepository;

    public function __construct(
        CommandBus $commandBus,
        UuidFactoryInterface $uuidFactory,
        CurrentUser $currentUser,
        OwnershipSearchRepository $ownershipSearchRepository
    ) {
        $this->commandBus = $commandBus;
        $this->uuidFactory = $uuidFactory;
        $this->currentUser = $currentUser;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::OWNERSHIP_POST),
            new DenormalizingRequestBodyParser(
                new RequestOwnershipDenormalizer(
                    $this->uuidFactory,
                    $this->currentUser
                ),
                RequestOwnership::class
            )
        );

        /** @var RequestOwnership $requestOwnership */
        $requestOwnership = $requestBodyParser->parse($request)->getParsedBody();

        // Make sure there is no open request for this item and owner
        try {
            $ownershipItem = $this->ownershipSearchRepository->getByItemIdAndOwnerId(
                $requestOwnership->getItemId()->toString(),
                $requestOwnership->getOwnerId()->toString()
            );
        } catch (OwnershipItemNotFound $e) {
            $ownershipItem = null;
        }

        if ($ownershipItem !== null) {
            throw ApiProblem::ownerShipAlreadyExists(
                'An ownership request for this item and owner already exists with id ' . $ownershipItem->getId()
            );
        }

        $this->commandBus->dispatch($requestOwnership);

        return new JsonResponse(
            [
                'id' => $requestOwnership->getId()->toString(),
            ],
            StatusCodeInterface::STATUS_CREATED
        );
    }
}
