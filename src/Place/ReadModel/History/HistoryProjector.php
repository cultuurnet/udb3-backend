<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\History\BaseHistoryProjector;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Offer\ReadModel\History\OfferHistoryProjectorTrait;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
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

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();
        switch (true) {
            case $event instanceof PlaceCreated:
                $this->projectPlaceCreated($domainMessage);
                break;
            case $event instanceof PlaceDeleted:
                $this->projectPlaceDeleted($domainMessage);
                break;
            case $event instanceof AddressUpdated:
                $this->projectAddressUpdated($domainMessage);
                break;
            case $event instanceof AddressTranslated:
                $this->projectAddressTranslated($domainMessage);
                break;
            case $event instanceof LabelAdded:
                $this->projectLabelAdded($domainMessage);
                break;
            case $event instanceof LabelRemoved:
                $this->projectLabelRemoved($domainMessage);
                break;
            case $event instanceof DescriptionTranslated:
                $this->projectDescriptionTranslated($domainMessage);
                break;
            case $event instanceof TitleTranslated:
                $this->projectTitleTranslated($domainMessage);
                break;
            case $event instanceof PlaceImportedFromUDB2:
                $this->projectPlaceImportedFromUDB2($domainMessage);
                break;
            case $event instanceof PlaceUpdatedFromUDB2:
                $this->projectPlaceUpdatedFromUDB2($domainMessage);
                break;
        }
    }

    private function projectPlaceCreated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Aangemaakt in UiTdatabank')
        );
    }

    private function projectPlaceDeleted(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Place verwijderd')
        );
    }

    private function projectPlaceImportedFromUDB2(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $event->getCdbXmlNamespaceUri(),
            $event->getCdbXml()
        );

        $udb2Log = Log::createFromDomainMessage($domainMessage, 'Aangemaakt in UDB2');

        if ($udb2Actor->getCreatedBy()) {
            $udb2Log = $udb2Log->withAuthor($udb2Actor->getCreatedBy());
        }

        $udb2Date = DateTime::createFromFormat(
            'Y-m-d?H:i:s',
            $udb2Actor->getCreationDate(),
            new DateTimeZone('Europe/Brussels')
        );
        if ($udb2Date) {
            $udb2Log = $udb2Log->withDate($udb2Date);
        }

        $this->writeHistory($domainMessage->getId(), $udb2Log);

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Geïmporteerd vanuit UDB2')
                ->withoutAuthor()
        );
    }

    private function projectPlaceUpdatedFromUDB2(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Geüpdatet vanuit UDB2')
                ->withoutAuthor()
        );
    }

    private function projectAddressUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Adres aangepast')
        );
    }

    private function projectAddressTranslated(DomainMessage $domainMessage): void
    {
        /* @var AddressTranslated $event */
        $event = $domainMessage->getPayload();
        $lang = $event->getLanguage()->getCode();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Adres vertaald ({$lang})")
        );
    }
}
