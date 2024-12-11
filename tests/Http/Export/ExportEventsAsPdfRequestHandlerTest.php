<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Export;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsPDF;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\TestCase;

final class ExportEventsAsPdfRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $commandBus;

    private ExportEventsAsPdfRequestHandler $exportEventsAsPdfRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->exportEventsAsPdfRequestHandler = new ExportEventsAsPdfRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_exporting_events_as_pdf(): void
    {
        $exportEventsAsPdfRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'query' => 'Dansvoorstellingen',
                'email' => 'jane@anonymous.com',
                'customizations' => [
                    'brand' => 'brand',
                    'logo' => 'logo',
                    'template' => 'map',
                    'title' => 'title',
                ],
            ])
            ->build('POST');

        $response = $this->exportEventsAsPdfRequestHandler->handle($exportEventsAsPdfRequest);

        $this->assertEquals(
            [
                (new ExportEventsAsPDF(
                    new EventExportQuery('Dansvoorstellingen'),
                    'brand',
                    'logo',
                    new Title('title'),
                    WebArchiveTemplate::map()
                ))->withEmailNotificationTo(new EmailAddress('jane@anonymous.com')),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new JsonResponse(['commandId' => Uuid::NIL]),
            $response
        );
    }
}
