<?php

namespace CultuurNet\UDB3\Model\Import\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class Udb3ModelToLegacyOrganizerAdapter implements LegacyOrganizer
{
    /**
     * @var Organizer
     */
    private $organizer;

    /**
     * @param Organizer $organizer
     */
    public function __construct(Organizer $organizer)
    {
        if (is_null($organizer->getUrl())) {
            throw new \InvalidArgumentException('Organizer URL required.');
        }

        $this->organizer = $organizer;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->organizer->getId()->toString();
    }

    /**
     * @inheritdoc
     */
    public function getMainLanguage()
    {
        return new Language(
            $this->organizer->getMainLanguage()->toString()
        );
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        $translatedTitle = $this->organizer->getName();

        return Title::fromUdb3ModelTitle(
            $translatedTitle->getTranslation(
                $translatedTitle->getOriginalLanguage()
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getWebsite()
    {
        return Url::fromNative($this->organizer->getUrl()->toString());
    }

    /**
     * @inheritdoc
     */
    public function getAddress()
    {
        $address = $this->organizer->getAddress();

        if ($address) {
            $address = $address->getTranslation($address->getOriginalLanguage());
            return Address::fromUdb3ModelAddress($address);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getContactPoint()
    {
        $contactPoint = $this->organizer->getContactPoint();
        return ContactPoint::fromUdb3ModelContactPoint($contactPoint);
    }

    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations()
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
    public function getAddressTranslations()
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
