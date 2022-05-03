<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateOnlineUrl;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class UpdateOnlineUrlRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateOnlineUrlRequestHandler $updateOnlineUrlRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->updateOnlineUrlRequestHandler = new UpdateOnlineUrlRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_dispatches_update_online_url(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'onlineUrl' => 'https://www.publiq.be/livestream',
            ])
            ->build('PUT');

        $response = $this->updateOnlineUrlRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new UpdateOnlineUrl('c269632a-a887-4f21-8455-1631c31e4df5', new Url('https://www.publiq.be/livestream')),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @dataProvider onlineUrlProvider
     * @test
     */
    public function it_throws_for_invalid_online_url(array $onlineUrl, SchemaError $schemaError): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray($onlineUrl)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData($schemaError),
            fn () => $this->updateOnlineUrlRequestHandler->handle($request)
        );
    }

    public function onlineUrlProvider(): array
    {
        return [
            [
                [
                    'onlineUrl' => '   ',
                ],
                new SchemaError('/onlineUrl', 'The data must match the \'uri\' format'),
            ],
            [
                [
                    'onlineUrl' => '',
                ],
                new SchemaError('/onlineUrl', 'The data must match the \'uri\' format'),
            ],
            [
                [
                    'onlineUrl' => 'wrong format',
                ],
                new SchemaError('/onlineUrl', 'The data must match the \'uri\' format'),
            ],
            [
                [
                    'onlineUrl' => 'rtp://www.publiq.be/livestream',
                ],
                new SchemaError('/onlineUrl', 'The string should match pattern: ^http[s]?:\/\/'),
            ],
        ];
    }
}
