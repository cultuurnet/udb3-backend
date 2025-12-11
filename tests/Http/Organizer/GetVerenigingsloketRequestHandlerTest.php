<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Verenigingsloket\Enum\VerenigingsloketConnectionStatus;
use CultuurNet\UDB3\Verenigingsloket\Exception\VerenigingsloketApiFailure;
use CultuurNet\UDB3\Verenigingsloket\Result\VerenigingsloketConnectionResult;
use CultuurNet\UDB3\Verenigingsloket\VerenigingsloketConnector;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetVerenigingsloketRequestHandlerTest extends TestCase
{
    private const ORGANIZER_ID = 'b3a0213a-9716-4555-9e72-77d4f8cf3cce';

    private VerenigingsloketConnector|MockObject $api;
    private GetVerenigingsloketConnectionRequestHandler $handler;
    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->api = $this->createMock(VerenigingsloketConnector::class);
        $this->handler = new GetVerenigingsloketConnectionRequestHandler($this->api);
        $this->psr7RequestBuilder = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', self::ORGANIZER_ID);
    }

    public function testHandleReturnsJsonResponseWhenVerenigingsloketConnectionFound(): void
    {
        $vcode = 'V123456';
        $url = 'https://www.verenigingsloket.be/nl/verenigingen/V123456';
        $relationId = '008583aa-6b6f-4ee0-a42b-1bc2a7f61be8';

        $this->api
            ->expects($this->once())
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->with(new Uuid(self::ORGANIZER_ID), VerenigingsloketConnectionStatus::CONFIRMED)
            ->willReturn(new VerenigingsloketConnectionResult($vcode, $url, $relationId, VerenigingsloketConnectionStatus::CONFIRMED));

        $response = $this->handler->handle($this->psr7RequestBuilder->build('GET'));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([
            'vcode' => $vcode,
            'url' => $url,
            'status' => VerenigingsloketConnectionStatus::CONFIRMED->value,
        ], Json::decodeAssociatively($response->getBody()->getContents()));
    }

    public function testHandleThrowsApiProblemWhenVerenigingsloketConnectionFails(): void
    {
        $this->api
            ->expects($this->once())
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->with(new Uuid(self::ORGANIZER_ID), VerenigingsloketConnectionStatus::CONFIRMED)
            ->willThrowException(new VerenigingsloketApiFailure('Failed to fetch token'));

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('Failed to connect to verenigingsloket');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE);

        $this->handler->handle($this->psr7RequestBuilder->build('GET'));
    }

    public function testHandleThrowsApiProblemWhenConnectionNotFound(): void
    {
        $callCount = 0;
        $this->api
            ->expects($this->exactly(2))
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->willReturnCallback(function (Uuid $organizerId, VerenigingsloketConnectionStatus $status) use (&$callCount) {
                $callCount++;
                $this->assertEquals(new Uuid(self::ORGANIZER_ID), $organizerId);

                if ($callCount === 1) {
                    $this->assertEquals(VerenigingsloketConnectionStatus::CONFIRMED, $status);
                } elseif ($callCount === 2) {
                    $this->assertEquals(VerenigingsloketConnectionStatus::CANCELLED, $status);
                }

                return null;
            });

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('Organizer b3a0213a-9716-4555-9e72-77d4f8cf3cce not found in verenigingsloket.');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        $this->handler->handle($this->psr7RequestBuilder->build('GET'));
    }

    public function testHandleFallsBackToCancelledStatusWhenConfirmedNotFound(): void
    {
        $vcode = 'V789012';
        $url = 'https://www.verenigingsloket.be/nl/verenigingen/V789012';
        $relationId = '123456aa-7b8f-4ee0-a42b-1bc2a7f61be8';

        $callCount = 0;
        $this->api
            ->expects($this->exactly(2))
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->willReturnCallback(function (Uuid $organizerId, VerenigingsloketConnectionStatus $status) use (&$callCount, $vcode, $url, $relationId) {
                $callCount++;
                $this->assertEquals(new Uuid(self::ORGANIZER_ID), $organizerId);

                if ($callCount === 1) {
                    $this->assertEquals(VerenigingsloketConnectionStatus::CONFIRMED, $status);
                    return null;
                } elseif ($callCount === 2) {
                    $this->assertEquals(VerenigingsloketConnectionStatus::CANCELLED, $status);
                    return new VerenigingsloketConnectionResult($vcode, $url, $relationId, VerenigingsloketConnectionStatus::CANCELLED);
                }

                return null;
            });

        $response = $this->handler->handle($this->psr7RequestBuilder->build('GET'));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([
            'vcode' => $vcode,
            'url' => $url,
            'status' => VerenigingsloketConnectionStatus::CANCELLED->value,
        ], Json::decodeAssociatively($response->getBody()->getContents()));
    }

    public function testHandlePrefernsConfirmedOverCancelledStatus(): void
    {
        $vcode = 'V555555';
        $url = 'https://www.verenigingsloket.be/nl/verenigingen/V555555';
        $relationId = '999999aa-8b9f-4ee0-a42b-1bc2a7f61be8';

        // Only CONFIRMED should be called since it returns a result
        $this->api
            ->expects($this->once())
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->with(new Uuid(self::ORGANIZER_ID), VerenigingsloketConnectionStatus::CONFIRMED)
            ->willReturn(new VerenigingsloketConnectionResult($vcode, $url, $relationId, VerenigingsloketConnectionStatus::CONFIRMED));

        $response = $this->handler->handle($this->psr7RequestBuilder->build('GET'));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([
            'vcode' => $vcode,
            'url' => $url,
            'status' => VerenigingsloketConnectionStatus::CONFIRMED->value,
        ], Json::decodeAssociatively($response->getBody()->getContents()));
    }
}
