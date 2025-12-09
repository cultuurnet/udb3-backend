<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
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
    private GetVerenigingsloketRequestHandler $handler;
    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->api = $this->createMock(VerenigingsloketConnector::class);
        $this->handler = new GetVerenigingsloketRequestHandler($this->api);
        $this->psr7RequestBuilder = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', self::ORGANIZER_ID);
    }

    public function testHandleReturnsJsonResponseWhenVereningslokketConnectionFound(): void
    {
        $vcode = 'V123456';
        $url = 'https://www.verenigingsloket.be/nl/verenigingen/V123456';
        $relationId = '008583aa-6b6f-4ee0-a42b-1bc2a7f61be8';

        $this->api
            ->expects($this->once())
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->with(new Uuid(self::ORGANIZER_ID))
            ->willReturn(new VerenigingsloketConnectionResult($vcode, $url, $relationId));

        $response = $this->handler->handle($this->psr7RequestBuilder->build('GET'));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([
            'vcode' => $vcode,
            'url' => $url,
        ], Json::decodeAssociatively($response->getBody()->getContents()));
    }

    public function testHandleThrowsApiProblemWhenUwpConnectionFails(): void
    {
        $this->api
            ->expects($this->once())
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->with(new Uuid(self::ORGANIZER_ID))
            ->willThrowException(new VerenigingsloketApiFailure('Failed to fetch token'));

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('Failed to connect to verenigingsloket');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE);

        $this->handler->handle($this->psr7RequestBuilder->build('GET'));
    }

    public function testHandleThrowsApiProblemWhenConnectionNotFound(): void
    {
        $this->api
            ->expects($this->once())
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->with(new Uuid(self::ORGANIZER_ID))
            ->willReturn(null);

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('Organizer b3a0213a-9716-4555-9e72-77d4f8cf3cce not found in verenigingsloket.');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        $this->handler->handle($this->psr7RequestBuilder->build('GET'));
    }
}
