<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\History\BaseHistoryProjector;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Offer\ReadModel\History\OfferHistoryProjectorTrait;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use DateTime;
use DateTimeZone;

final class HistoryProjector extends BaseHistoryProjector
{
    use OfferHistoryProjectorTrait;

    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();
        switch (true) {
            case $event instanceof PlaceCreated:
                $this->projectPlaceCreated($event, $domainMessage);
                break;
            case $event instanceof PlaceDeleted:
                $this->projectPlaceDeleted($event, $domainMessage);
                break;
            case $event instanceof LabelAdded:
                $this->projectLabelAdded($event, $domainMessage);
                break;
            case $event instanceof LabelRemoved:
                $this->projectLabelRemoved($event, $domainMessage);
                break;
            case $event instanceof DescriptionTranslated:
                $this->projectDescriptionTranslated($event, $domainMessage);
                break;
            case $event instanceof TitleTranslated:
                $this->projectTitleTranslated($event, $domainMessage);
                break;
            case $event instanceof PlaceImportedFromUDB2:
                $this->projectPlaceImportedFromUDB2($event, $domainMessage);
                break;
            case $event instanceof PlaceUpdatedFromUDB2:
                $this->projectPlaceUpdatedFromUDB2($event, $domainMessage);
                break;
        }
    }

    private function projectPlaceCreated(PlaceCreated $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getPlaceId(),
            $this->createGenericLog($domainMessage, 'Aangemaakt in UiTdatabank')
        );
    }

    private function projectPlaceDeleted(PlaceDeleted $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, 'Place verwijderd')
        );
    }

    private function projectPlaceImportedFromUDB2(PlaceImportedFromUDB2 $event, DomainMessage $domainMessage): void
    {
        $udb2Event = EventItemFactory::createEventFromCdbXml(
            $event->getCdbXmlNamespaceUri(),
            $event->getCdbXml()
        );

        $this->writeHistory(
            $event->getActorId(),
            new Log(
                DateTime::createFromFormat(
                    'Y-m-d?H:i:s',
                    $udb2Event->getCreationDate(),
                    new DateTimeZone('Europe/Brussels')
                ),
                'Aangemaakt in UDB2',
                $udb2Event->getCreatedBy()
            )
        );

        $this->writeHistory(
            $event->getActorId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                'Geïmporteerd vanuit UDB2',
                null,
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata()),
                $this->getConsumerFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    private function projectPlaceUpdatedFromUDB2(PlaceUpdatedFromUDB2 $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getActorId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                'Geüpdatet vanuit UDB2',
                null,
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata()),
                $this->getConsumerFromMetadata($domainMessage->getMetadata())
            )
        );
    }
}
