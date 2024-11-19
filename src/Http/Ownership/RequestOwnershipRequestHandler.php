<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Ownership\Serializers\RequestOwnershipDenormalizer;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
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
    private DocumentRepository $organizerRepository;
    private OwnershipStatusGuard $ownershipStatusGuard;
    private UserIdentityResolver $identityResolver;

    public function __construct(
        CommandBus $commandBus,
        UuidFactoryInterface $uuidFactory,
        CurrentUser $currentUser,
        OwnershipSearchRepository $ownershipSearchRepository,
        DocumentRepository $organizerRepository,
        OwnershipStatusGuard $ownershipStatusGuard,
        UserIdentityResolver $identityResolver
    ) {
        $this->commandBus = $commandBus;
        $this->uuidFactory = $uuidFactory;
        $this->currentUser = $currentUser;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->organizerRepository = $organizerRepository;
        $this->ownershipStatusGuard = $ownershipStatusGuard;
        $this->identityResolver = $identityResolver;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::OWNERSHIP_POST),
            new DenormalizingRequestBodyParser(
                new RequestOwnershipDenormalizer(
                    $this->uuidFactory,
                    $this->identityResolver,
                    $this->currentUser
                ),
                RequestOwnership::class
            )
        );

        /** @var RequestOwnership $requestOwnership */
        $requestOwnership = $requestBodyParser->parse($request)->getParsedBody();

        // Make sure the organizer does exist
        if ($requestOwnership->getItemType()->sameAs(ItemType::organizer())) {
            try {
                $this->organizerRepository->fetch($requestOwnership->getItemId()->toString());
            } catch (DocumentDoesNotExist $e) {
                throw ApiProblem::organizerNotFound($requestOwnership->getItemId()->toString());
            }
        }

        // Make sure the current user is allowed to request ownership for this organizer
        $this->ownershipStatusGuard->isAllowedToRequest(
            $requestOwnership->getItemId()->toString(),
            $requestOwnership->getOwnerId()->toString(),
            $this->currentUser
        );

        // Make sure there is no open request for this item and owner
        $existingOwnershipItems = $this->ownershipSearchRepository->search(
            new SearchQuery([
                new SearchParameter('itemId', $requestOwnership->getItemId()->toString()),
                new SearchParameter('ownerId', $requestOwnership->getOwnerId()->toString()),
            ])
        );

        /** @var OwnershipItem $ownershipItem */
        foreach ($existingOwnershipItems as $ownershipItem) {
            if ($ownershipItem->getState() === OwnershipState::requested()->toString() ||
                $ownershipItem->getState() === OwnershipState::approved()->toString()) {
                throw ApiProblem::ownerShipAlreadyExists(
                    'An ownership request for this item and owner already exists with id ' . $ownershipItem->getId()
                );
            }
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
