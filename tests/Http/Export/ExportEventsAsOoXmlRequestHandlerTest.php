<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Export;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXML;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\TestCase;

final class ExportEventsAsOoXmlRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $commandBus;

    private ExportEventsAsOoXmlRequestHandler $exportEventsAsOoXmlRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->exportEventsAsOoXmlRequestHandler = new ExportEventsAsOoXmlRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_exporting_events_as_oo_xml(): void
    {
        $exportEventsAsOoXmlRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'include' => [
                    'calendarSummary',
                ],
                'query' => 'Dansvoorstellingen',
                'email' => 'jane@anonymous.com',
            ])
            ->build('POST');

        $response = $this->exportEventsAsOoXmlRequestHandler->handle($exportEventsAsOoXmlRequest);

        $this->assertEquals(
            [
                new ExportEventsAsOOXML(
                    new EventExportQuery('Dansvoorstellingen'),
                    ['calendarSummary'],
                    new EmailAddress('jane@anonymous.com')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new JsonResponse(['commandId' => Uuid::NIL]),
            $response
        );
    }
}
