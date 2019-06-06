<?php

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\EventExport\Command\ExportEventsInterface;
use Psr\Log\LoggerInterface;

class EventExportServiceCollection
{
    /**
     * @var EventExportServiceInterface[]
     */
    private $eventExportServices;

    /**
     * @param SapiVersion $sapiVersion
     * @param EventExportServiceInterface $eventExportService
     * @return EventExportServiceCollection
     */
    public function withService(
        SapiVersion $sapiVersion,
        EventExportServiceInterface $eventExportService
    ): EventExportServiceCollection {
        $c = clone $this;
        $c->eventExportServices[$sapiVersion->toNative()] = $eventExportService;
        return $c;
    }

    /**
     * @param SapiVersion $sapiVersion
     * @return EventExportServiceInterface|null
     */
    public function getService(SapiVersion $sapiVersion): ?EventExportServiceInterface
    {
        if (!isset($this->eventExportServices[$sapiVersion->toNative()])) {
            return null;
        }

        return $this->eventExportServices[$sapiVersion->toNative()];
    }

    /**
     * @param FileFormatInterface $fileFormat
     * @param ExportEventsInterface $exportEvents
     */
    public function delegateToServiceWithAppropriateSapiVersion(
        FileFormatInterface $fileFormat,
        ExportEventsInterface $exportEvents,
        LoggerInterface $logger
    ): void {
        $eventExportService = $this->getService(
            $exportEvents->getSapiVersion()
        );

        $eventExportService->exportEvents(
            $fileFormat,
            $exportEvents->getQuery(),
            $exportEvents->getAddress(),
            $logger,
            $exportEvents->getSelection()
        );
    }
}
