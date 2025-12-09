<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Verenigingsloket\Exception\VerenigingsloketApiFailure;
use CultuurNet\UDB3\Verenigingsloket\VerenigingsloketConnector;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteVerenigingsloketConnectionRequestHandlerTest extends TestCase
{
    private const ORGANIZER_ID = 'b3a0213a-9716-4555-9e72-77d4f8cf3cce';
    private const CURRENT_USER_ID = 'user-123';

    private VerenigingsloketConnector|MockObject $api;
    private PermissionVoter|MockObject $voter;
    private DeleteVerenigingsloketConnectionRequestHandler $handler;
    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->api = $this->createMock(VerenigingsloketConnector::class);
        $this->voter = $this->createMock(PermissionVoter::class);
        $this->handler = new DeleteVerenigingsloketConnectionRequestHandler(
            $this->api,
            $this->voter,
            self::CURRENT_USER_ID
        );
        $this->psr7RequestBuilder = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', self::ORGANIZER_ID);
    }

    public function testHandleReturnsNoContentWhenConnectionSuccessfullyDeleted(): void
    {
        $this->voter
            ->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodBewerken(), self::ORGANIZER_ID, self::CURRENT_USER_ID)
            ->willReturn(true);

        $this->api
            ->expects($this->once())
            ->method('breakRelationFromVerenigingsloket')
            ->with(new Uuid(self::ORGANIZER_ID), self::CURRENT_USER_ID)
            ->willReturn(true);

        $response = $this->handler->handle($this->psr7RequestBuilder->build('DELETE'));

        $this->assertEquals(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals([], Json::decodeAssociatively($response->getBody()->getContents()));
    }

    public function testHandleThrowsCannotDeleteExceptionWhenUserNotAllowed(): void
    {
        $this->voter
            ->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodBewerken(), self::ORGANIZER_ID, self::CURRENT_USER_ID)
            ->willReturn(false);

        $this->api
            ->expects($this->never())
            ->method('breakRelationFromVerenigingsloket');

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('Only owners can delete verenigingsloket matches');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_FORBIDDEN);

        $this->handler->handle($this->psr7RequestBuilder->build('DELETE'));
    }

    public function testHandleThrowsApiProblemWhenApiCallFails(): void
    {
        $this->voter
            ->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodBewerken(), self::ORGANIZER_ID, self::CURRENT_USER_ID)
            ->willReturn(true);

        $this->api
            ->expects($this->once())
            ->method('breakRelationFromVerenigingsloket')
            ->with(new Uuid(self::ORGANIZER_ID), self::CURRENT_USER_ID)
            ->willThrowException(VerenigingsloketApiFailure::apiUnavailable('Connection failed'));

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('Failed to connect to verenigingsloket');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE);

        $this->handler->handle($this->psr7RequestBuilder->build('DELETE'));
    }

    public function testHandleThrowsApiProblemWhenConnectionNotFound(): void
    {
        $this->voter
            ->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodBewerken(), self::ORGANIZER_ID, self::CURRENT_USER_ID)
            ->willReturn(true);

        $this->api
            ->expects($this->once())
            ->method('breakRelationFromVerenigingsloket')
            ->with(new Uuid(self::ORGANIZER_ID), self::CURRENT_USER_ID)
            ->willReturn(false);

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('Organizer b3a0213a-9716-4555-9e72-77d4f8cf3cce not found in verenigingsloket.');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        $this->handler->handle($this->psr7RequestBuilder->build('DELETE'));
    }
}
