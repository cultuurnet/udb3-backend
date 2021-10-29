<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

final class CreateOrganizer
{
    private string $organizerId;

    private Language $mainLanguage;

    private Url $website;

    private Title $title;

    public function __construct(
        string $organizerId,
        Language $mainLanguage,
        Url $website,
        Title $title
    ) {
        $this->organizerId = $organizerId;
        $this->website = $website;
        $this->mainLanguage = $mainLanguage;
        $this->title = $title;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    public function getWebsite(): Url
    {
        return $this->website;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }
}
