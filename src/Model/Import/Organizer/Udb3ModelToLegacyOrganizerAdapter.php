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
        $this->organizer = $organizer;
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
}
