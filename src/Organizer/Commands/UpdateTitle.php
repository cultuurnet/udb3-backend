<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class UpdateTitle extends AbstractUpdateOrganizerCommand
{
    private Title $title;

    private Language $language;

    public function __construct(
        string $organizerId,
        Title $title,
        Language $language
    ) {
        parent::__construct($organizerId);
        $this->title = $title;
        $this->language = $language;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }
}
