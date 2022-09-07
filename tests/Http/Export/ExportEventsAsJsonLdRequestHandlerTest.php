<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Export;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLD;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\TestCase;

final class ExportEventsAsJsonLdRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const LABEL_NAME = 'Some new Label';

    private TraceableCommandBus $commandBus;

    private ExportEventsAsJsonLdRequestHandler $exportEventsAsJsonLdRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->exportEventsAsJsonLdRequestHandler = new ExportEventsAsJsonLdRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_exporting_offers_as_json_ld(): void
    {
        $exportEventsAsJsonLdRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'query' => 'Dansvoorstellingen',
                'email' => 'jane@anonymous.com',
            ])
            ->build('POST');

        $response = $this->exportEventsAsJsonLdRequestHandler->handle($exportEventsAsJsonLdRequest);

        $this->assertEquals(
            [
                new ExportEventsAsJsonLD(
                    new EventExportQuery('Dansvoorstellingen'),
                    new EmailAddress('jane@anonymous.com')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new JsonResponse(['commandId' => '00000000-0000-0000-0000-000000000000']),
            $response
        );
    }
}
