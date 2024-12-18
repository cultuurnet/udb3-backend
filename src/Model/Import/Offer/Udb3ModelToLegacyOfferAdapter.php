<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\Model\Offer\Offer;
use DateTimeImmutable;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
class Udb3ModelToLegacyOfferAdapter implements LegacyOffer
{
    private Offer $offer;

    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    public function getOrganizerId(): ?string
    {
        $reference = $this->offer->getOrganizerReference();

        if ($reference) {
            return $reference->getOrganizerId()->toString();
        }

        return null;
    }

    public function getAvailableFrom(DateTimeImmutable $default): DateTimeImmutable
    {
        $availableFrom = $this->offer->getAvailableFrom();
        if (!$availableFrom || $availableFrom < $default) {
            $availableFrom = $default;
        }
        return $availableFrom;
    }

    public function getTitleTranslations(): array
    {
        $titles = [];

        /* @var \CultuurNet\UDB3\Model\ValueObject\Translation\Language $language */
        $translatedTitle = $this->offer->getTitle();
        foreach ($translatedTitle->getLanguagesWithoutOriginal() as $language) {
            $titles[$language->toString()] = $translatedTitle->getTranslation($language);
        }

        return $titles;
    }
}
