<?php

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLD;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ValueObjects\Web\EmailAddress;

class EventExportServiceCollectionTest extends TestCase
{
    /**
     * @var \CultuurNet\UDB3\EventExport\SapiVersion
     */
    private $sapi3;

    /**
     * @var \CultuurNet\UDB3\EventExport\SapiVersion
     */
    private $sapi2;

    /**
     * @var \CultuurNet\UDB3\EventExport\EventExportService|MockObject
     */
    private $serviceForSapi2;

    /**
     * @var \CultuurNet\UDB3\EventExport\EventExportService|MockObject
     */
    private $serviceForSapi3;

    /**
     * @var \CultuurNet\UDB3\EventExport\EventExportServiceCollection
     */
    private $collection;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->sapi2 = new SapiVersion(SapiVersion::V2);
        $this->sapi3 = new SapiVersion(SapiVersion::V3);

        $this->serviceForSapi2 = $this->createMock(EventExportServiceInterface::class);
        $this->serviceForSapi3 = $this->createMock(EventExportServiceInterface::class);

        $this->collection = (new EventExportServiceCollection())
            ->withService(
                $this->sapi2,
                $this->serviceForSapi2
            )
            ->withService(
                $this->sapi3,
                $this->serviceForSapi3
            );
    }

    /**
     * @test
     */
    public function it_has_multiple_event_export_services(): void
    {
        $this->assertEquals(
            $this->serviceForSapi2,
            $this->collection->getService($this->sapi2)
        );
        $this->assertEquals(
            $this->serviceForSapi3,
            $this->collection->getService($this->sapi3)
        );
    }

    /**
     * @test
     */
    public function it_delegates_to_the_appropriate_sapi_version(): void
    {
        $email = new EmailAddress('jane@anonymous.com');

        $exportJsonLDWithSapi2 = new ExportEventsAsJsonLD(
            new EventExportQuery('title:test-on-sapi-2'),
            $this->sapi2,
            $email,
            null,
            null
        );

        $fileFormat = $this->getMockBuilder(FileFormatInterface::class)
            ->getMock();

        $logger = new NullLogger();
        
        $this->serviceForSapi2->expects($this->once())
            ->method('exportEvents')
            ->with(
                $fileFormat,
                new EventExportQuery('title:test-on-sapi-2'),
                $email,
                $logger,
                null
            );
        
        $this->collection->delegateToServiceWithAppropriateSapiVersion(
            $fileFormat,
            $exportJsonLDWithSapi2,
            $logger
        );

        $exportJsonLDWithSapi3 = new ExportEventsAsJsonLD(
            new EventExportQuery('title:test-on-sapi-3'),
            $this->sapi3,
            $email,
            null,
            null
        );

        $this->serviceForSapi3->expects($this->once())
            ->method('exportEvents')
            ->with(
                $fileFormat,
                new EventExportQuery('title:test-on-sapi-3'),
                $email,
                $logger,
                null
            );

        $this->collection->delegateToServiceWithAppropriateSapiVersion(
            $fileFormat,
            $exportJsonLDWithSapi3,
            new NullLogger()
        );
    }
}
