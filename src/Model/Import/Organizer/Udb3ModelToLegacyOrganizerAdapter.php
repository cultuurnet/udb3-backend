<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class Udb3ModelToLegacyOrganizerAdapter implements LegacyOrganizer
{
    private Organizer $organizer;

    public function __construct(Organizer $organizer)
    {
        if (is_null($organizer->getUrl())) {
            throw new \InvalidArgumentException('Organizer URL required.');
        }

        $this->organizer = $organizer;
    }

    public function getId(): string
    {
        return $this->organizer->getId()->toString();
    }

    public function getMainLanguage(): Language
    {
        return new Language(
            $this->organizer->getMainLanguage()->toString()
        );
    }

    public function getTitle(): Title
    {
        $translatedTitle = $this->organizer->getName();

        return Title::fromUdb3ModelTitle(
            $translatedTitle->getTranslation(
                $translatedTitle->getOriginalLanguage()
            )
        );
    }

    public function getWebsite(): Url
    {
        return Url::fromNative($this->organizer->getUrl()->toString());
    }

    public function getAddress(): ?Address
    {
        $address = $this->organizer->getAddress();

        if ($address) {
            $address = $address->getTranslation($address->getOriginalLanguage());
            return Address::fromUdb3ModelAddress($address);
        } else {
            return null;
        }
    }

    public function getContactPoint(): ContactPoint
    {
        $contactPoint = $this->organizer->getContactPoint();
        return ContactPoint::fromUdb3ModelContactPoint($contactPoint);
    }

    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations(): array
    {
        $titles = [];

        /* @var \CultuurNet\UDB3\Model\ValueObject\Translation\Language $language */
        $translatedTitle = $this->organizer->getName();
        foreach ($translatedTitle->getLanguagesWithoutOriginal() as $language) {
            $titles[$language->toString()] = Title::fromUdb3ModelTitle(
                $translatedTitle->getTranslation($language)
            );
        }

        return $titles;
    }

    /**
     * @return Address[]
     *   Language code as key, and Address as value.
     */
    public function getAddressTranslations(): array
    {
        $addresses = [];

        /* @var \CultuurNet\UDB3\Model\ValueObject\Translation\Language $language */
        $translatedAddress = $this->organizer->getAddress();

        if (!$translatedAddress) {
            return [];
        }

        foreach ($translatedAddress->getLanguagesWithoutOriginal() as $language) {
            $addresses[$language->toString()] = Address::fromUdb3ModelAddress(
                $translatedAddress->getTranslation($language)
            );
        }

        return $addresses;
    }
}
