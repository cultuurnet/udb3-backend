<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\User\CurrentUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as Uuidv4;
use Ramsey\Uuid\UuidFactoryInterface;

class RequestOwnershipRequestHandlerTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    /** @var UuidFactoryInterface|MockObject */
    private $uuidFactory;

    private RequestOwnershipRequestHandler $requestOwnershipRequestHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);

        $this->requestOwnershipRequestHandler = new RequestOwnershipRequestHandler(
            $this->commandBus,
            $this->uuidFactory,
            new CurrentUser('google-oauth2|102486314601596809843')
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

        $response = $this->requestOwnershipRequestHandler->handle($request);

        $this->assertEquals(
            [
                'id' => 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            ],
            json_decode((string) $response->getBody(), true)
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
                    new UserId('google-oauth2|102486314601596809843')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
