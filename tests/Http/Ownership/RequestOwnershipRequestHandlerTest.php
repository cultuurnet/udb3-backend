<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as Uuidv4;
use Ramsey\Uuid\UuidFactoryInterface;

class RequestOwnershipRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    /** @var UuidFactoryInterface&MockObject */
    private $uuidFactory;

    /** @var OwnershipSearchRepository&MockObject */
    private $ownerShipSearchRepository;

    /** @var UserIdentityResolver&MockObject */
    private $identityResolver;

    /** @var PermissionVoter&MockObject */
    private $permissionVoter;

    private RequestOwnershipRequestHandler $requestOwnershipRequestHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);

        $this->ownerShipSearchRepository = $this->createMock(OwnershipSearchRepository::class);
        $this->identityResolver = $this->createMock(UserIdentityResolver::class);

        $organizerRepository = new InMemoryDocumentRepository();
        $organizerRepository->save(new JsonDocument('9e68dafc-01d8-4c1c-9612-599c918b981d'));

        $this->permissionVoter = $this->createMock(PermissionVoter::class);

        $this->requestOwnershipRequestHandler = new RequestOwnershipRequestHandler(
            $this->commandBus,
            $this->uuidFactory,
            new CurrentUser('auth0|63e22626e39a8ca1264bd29b'),
            $this->ownerShipSearchRepository,
            $organizerRepository,
            new OwnershipStatusGuard(
                $this->ownerShipSearchRepository,
                $this->permissionVoter
            ),
            $this->identityResolver
        );
    }

    /**
     * @test
     */
    public function it_handles_requesting_ownership(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'organizer',
                'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
            ])
            ->build('POST');

        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn(Uuidv4::fromString('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'));

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getByItemIdAndOwnerId')
            ->with(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willThrowException(OwnershipItemNotFound::byItemIdAndOwnerId(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            ));

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBeheren(),
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willReturn(true);

        $response = $this->requestOwnershipRequestHandler->handle($request);

        $this->assertEquals(
            [
                'id' => 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            ],
            Json::decodeAssociatively((string)$response->getBody())
        );

        $this->assertEquals(
            201,
            $response->getStatusCode()
        );

        $this->assertEquals(
            [
                new RequestOwnership(
                    new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                    new UUID('9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ItemType::organizer(),
                    new UserId('auth0|63e22626e39a8ca1264bd29b'),
                    new UserId('auth0|63e22626e39a8ca1264bd29b')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_prevents_requesting_ownership_when_no_permission(): void
    {
        CurrentUser::configureGodUserIds([]);

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'organizer',
                'ownerId' => 'google-oauth2|102486314601596809843',
            ])
            ->build('POST');

        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn(Uuidv4::fromString('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'));

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getByItemIdAndOwnerId')
            ->with(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'google-oauth2|102486314601596809843'
            )
            ->willThrowException(OwnershipItemNotFound::byItemIdAndOwnerId(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'google-oauth2|102486314601596809843'
            ));

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBeheren(),
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willReturn(false);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('You are not allowed to request ownership for this item'),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_handles_requesting_ownership_with_email(): void
    {
        CurrentUser::configureGodUserIds([]);

        $this->identityResolver->expects($this->once())
            ->method('getUserByEmail')
            ->with(new EmailAddress('dev+e2etest@publiq.be'))
            ->willReturn(new UserIdentityDetails(
                'auth0|63e22626e39a8ca1264bd29b',
                'e2e',
                'dev+e2etest@publiq.be'
            ));

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'organizer',
                'ownerEmail' => 'dev+e2etest@publiq.be',
            ])
            ->build('POST');
        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn(Uuidv4::fromString('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'));

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getByItemIdAndOwnerId')
            ->with(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willThrowException(OwnershipItemNotFound::byItemIdAndOwnerId(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            ));

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBeheren(),
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willReturn(true);

        $response = $this->requestOwnershipRequestHandler->handle($request);

        $this->assertEquals(
            [
                'id' => 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            ],
            Json::decodeAssociatively((string)$response->getBody())
        );

        $this->assertEquals(
            201,
            $response->getStatusCode()
        );

        $this->assertEquals(
            [
                new RequestOwnership(
                    new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                    new UUID('9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ItemType::organizer(),
                    new UserId('auth0|63e22626e39a8ca1264bd29b'),
                    new UserId('auth0|63e22626e39a8ca1264bd29b')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_allows_requesting_ownership_for_yourself_even_without_permission(): void
    {
        CurrentUser::configureGodUserIds([]);

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'organizer',
                'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
            ])
            ->build('POST');

        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn(Uuidv4::fromString('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'));

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getByItemIdAndOwnerId')
            ->with(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willThrowException(OwnershipItemNotFound::byItemIdAndOwnerId(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            ));

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBeheren(),
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willReturn(false);

        $response = $this->requestOwnershipRequestHandler->handle($request);

        $this->assertEquals(
            201,
            $response->getStatusCode()
        );

        $this->assertEquals(
            [
                new RequestOwnership(
                    new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                    new UUID('9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ItemType::organizer(),
                    new UserId('auth0|63e22626e39a8ca1264bd29b'),
                    new UserId('auth0|63e22626e39a8ca1264bd29b')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_prevents_requesting_same_ownership(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'organizer',
                'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
            ])
            ->build('POST');

        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn(Uuidv4::fromString('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'));

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getByItemIdAndOwnerId')
            ->with(
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willReturn(new OwnershipItem(
                'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'organizer',
                'auth0|63e22626e39a8ca1264bd29b',
                OwnershipState::requested()->toString()
            ));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::ownerShipAlreadyExists(
                'An ownership request for this item and owner already exists with id e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'
            ),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_prevents_requesting_ownership_for_non_existing_organizer(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => 'fc93ceb0-e170-4d92-b496-846b2a194f1c',
                'itemType' => 'organizer',
                'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
            ])
            ->build('POST');

        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn(Uuidv4::fromString('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'));

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getByItemIdAndOwnerId')
            ->with(
                'fc93ceb0-e170-4d92-b496-846b2a194f1c',
                'auth0|63e22626e39a8ca1264bd29b'
            )
            ->willThrowException(OwnershipItemNotFound::byItemIdAndOwnerId(
                'fc93ceb0-e170-4d92-b496-846b2a194f1c',
                'auth0|63e22626e39a8ca1264bd29b'
            ));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::organizerNotFound('fc93ceb0-e170-4d92-b496-846b2a194f1c'),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throw_if_body_is_missing(): void
    {
        $request = (new Psr7RequestBuilder())
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_missing_item_id(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemType' => 'organizer',
                'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
            ])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/',
                    'The data should match exactly one schema'
                ),
            ),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_missing_item_type(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
            ])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/',
                    'The data should match exactly one schema'
                ),
            ),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_missing_owner_id(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'organizer',
            ])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/',
                    'The data should match exactly one schema'
                ),
            ),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_item_id(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '123',
                'itemType' => 'organizer',
                'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
            ])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/itemId',
                    'The string should match pattern: [0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12}'
                ),
            ),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_item_type(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'invalid',
                'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
            ])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/itemType',
                    'The data should match one item from enum'
                ),
            ),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_empty_owner_id(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'organizer',
                'ownerId' => '',
            ])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/ownerId',
                    'Minimum string length is 1, found 0'
                ),
            ),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_owner_id_type(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'itemType' => 'organizer',
                'ownerId' => 123,
            ])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/ownerId',
                    'The data (integer) must match the type: string'
                ),
            ),
            fn () => $this->requestOwnershipRequestHandler->handle($request)
        );
    }
}
