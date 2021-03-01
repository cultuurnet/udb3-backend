<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Offer\Offer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

class Udb3ModelToLegacyOfferAdapter implements LegacyOffer
{
    /**
     * @var Offer
     */
    private $offer;


    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->offer->getId()->toString();
    }

    /**
     * @inheritdoc
     */
    public function getMainLanguage()
    {
        return Language::fromUdb3ModelLanguage(
            $this->offer->getMainLanguage()
        );
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        $translatedTitle = $this->offer->getTitle();

        return Title::fromUdb3ModelTitle(
            $translatedTitle->getTranslation(
                $translatedTitle->getOriginalLanguage()
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        $translatedDescription = $this->offer->getDescription();

        if ($translatedDescription) {
            $description = $translatedDescription->getTranslation($translatedDescription->getOriginalLanguage());
            return Description::fromUdb3ModelDescription($description);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getType()
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

    /**
     * @inheritdoc
     */
    public function getTheme()
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

    /**
     * @inheritdoc
     */
    public function getCalendar()
    {
        return Calendar::fromUdb3ModelCalendar($this->offer->getCalendar());
    }

    /**
     * @inheritdoc
     */
    public function getOrganizerId()
    {
        $reference = $this->offer->getOrganizerReference();

        if ($reference) {
            return $reference->getOrganizerId()->toString();
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getAgeRange()
    {
        $ageRange = $this->offer->getAgeRange();

        if ($ageRange) {
            return AgeRange::fromUbd3ModelAgeRange($ageRange);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getPriceInfo()
    {
        $priceInfo = $this->offer->getPriceInfo();

        if ($priceInfo) {
            return PriceInfo::fromUdb3ModelPriceInfo($priceInfo);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getBookingInfo()
    {
        $bookingInfo = $this->offer->getBookingInfo();
        return BookingInfo::fromUdb3ModelBookingInfo($bookingInfo);
    }

    /**
     * @inheritdoc
     */
    public function getContactPoint()
    {
        $contactPoint = $this->offer->getContactPoint();
        return ContactPoint::fromUdb3ModelContactPoint($contactPoint);
    }

    /**
     * @inheritdoc
     */
    public function getAvailableFrom(\DateTimeImmutable $default = null)
    {
        $availableFrom = $this->offer->getAvailableFrom();
        if (!$availableFrom) {
            $availableFrom = $default;
        }
        return $availableFrom;
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslations()
    {
        $titles = [];

        /* @var \CultuurNet\UDB3\Model\ValueObject\Translation\Language $language */
        $translatedTitle = $this->offer->getTitle();
        foreach ($translatedTitle->getLanguagesWithoutOriginal() as $language) {
            $titles[$language->toString()] = Title::fromUdb3ModelTitle(
                $translatedTitle->getTranslation($language)
            );
        }

        return $titles;
    }

    /**
     * @inheritdoc
     */
    public function getDescriptionTranslations()
    {
        $descriptions = [];

        /* @var \CultuurNet\UDB3\Model\ValueObject\Translation\Language $language */
        $translatedDescription = $this->offer->getDescription();

        if (!$translatedDescription) {
            return [];
        }

        foreach ($translatedDescription->getLanguagesWithoutOriginal() as $language) {
            $descriptions[$language->toString()] = Description::fromUdb3ModelDescription(
                $translatedDescription->getTranslation($language)
            );
        }

        return $descriptions;
    }
}
