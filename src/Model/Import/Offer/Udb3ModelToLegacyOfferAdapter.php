<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\Offer\Offer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Theme;
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

    public function getId(): string
    {
        return $this->offer->getId()->toString();
    }

    public function getMainLanguage(): Language
    {
        return $this->offer->getMainLanguage();
    }

    public function getType(): EventType
    {
        $type = $this->offer->getTerms()
            ->filter(
                function (Category $term) {
                    $domain = $term->getDomain();
                    return $domain && $domain->sameAs(new CategoryDomain('eventtype'));
                }
            )
            ->getFirst();

        return EventType::fromUdb3ModelCategory($type);
    }

    public function getTheme(): ?Theme
    {
        $theme = $this->offer->getTerms()
            ->filter(
                function (Category $term) {
                    $domain = $term->getDomain();
                    return $domain && $domain->sameAs(new CategoryDomain('theme'));
                }
            )
            ->getFirst();

        return $theme ? Theme::fromUdb3ModelCategory($theme) : null;
    }

    public function getCalendar(): Calendar
    {
        return Calendar::fromUdb3ModelCalendar($this->offer->getCalendar());
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
