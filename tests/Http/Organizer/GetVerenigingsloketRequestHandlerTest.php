<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Uitwisselingsplatform\Exception\UwpApiFailure;
use CultuurNet\UDB3\Uitwisselingsplatform\Result\VerenigingsloketConnectionResult;
use CultuurNet\UDB3\Uitwisselingsplatform\UitwisselingsplatformApiConnector;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetVerenigingsloketRequestHandlerTest extends TestCase
{
    private const ORGANIZER_ID = 'b3a0213a-9716-4555-9e72-77d4f8cf3cce';

    private UitwisselingsplatformApiConnector|MockObject $uwpApiConnector;
    private GetVerenigingsloketRequestHandler $handler;
    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->uwpApiConnector = $this->createMock(UitwisselingsplatformApiConnector::class);
        $this->handler = new GetVerenigingsloketRequestHandler($this->uwpApiConnector);
        $this->psr7RequestBuilder = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', self::ORGANIZER_ID);
    }

    public function testHandleReturnsJsonResponseWhenVereningslokketConnectionFound(): void
    {
        $vcode = 'V123456';
        $url = 'https://www.verenigingsloket.be/nl/verenigingen/V123456';

        $this->uwpApiConnector
            ->expects($this->once())
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->with(new Uuid(self::ORGANIZER_ID))
            ->willReturn(new VerenigingsloketConnectionResult($vcode, $url));

        $response = $this->handler->handle($this->psr7RequestBuilder->build('GET'));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([
            'vcode' => $vcode,
            'url' => $url,
        ], Json::decodeAssociatively($response->getBody()->getContents()));
    }

    public function testHandleThrowsApiProblemWhenUwpConnectionFails(): void
    {
        $this->uwpApiConnector
            ->expects($this->once())
            ->method('fetchVerenigingsloketConnectionForOrganizer')
            ->with(new Uuid(self::ORGANIZER_ID))
            ->willThrowException(new UwpApiFailure('Failed to fetch token'));

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('Failed to connect to UiTWisselingsplatform');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE);

        $this->handler->handle($this->psr7RequestBuilder->build('GET'));
    }

    public function testHandleThrowsApiProblemWhenVereningslokketConnectionNotFound(): void
    {
        $this->uwpApiConnector
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
