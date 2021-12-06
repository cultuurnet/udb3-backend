<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

final class OrganizerCreatedWithUniqueWebsite extends OrganizerEvent
{
    private string $mainLanguage;

    private string $website;

    private string $title;

    public function __construct(
        string $id,
        string $mainLanguage,
        string $website,
        string $title
    ) {
        parent::__construct($id);

        $this->mainLanguage = $mainLanguage;
        $this->website = $website;
        $this->title = $title;
    }

    public function getMainLanguage(): string
    {
        return $this->mainLanguage;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'main_language' => $this->mainLanguage,
            'website' => $this->website,
            'title' => $this->title,
        ];
    }

    public static function deserialize(array $data): OrganizerCreatedWithUniqueWebsite
    {
        return new static(
            $data['organizer_id'],
            $data['main_language'],
            $data['website'],
            $data['title']
        );
    }
}
