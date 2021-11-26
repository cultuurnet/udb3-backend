<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;

final class TitleTranslated extends OrganizerEvent
{
    private string $title;
    private string $language;

    public function __construct(
        string $organizerId,
        string $title,
        string $language
    ) {
        parent::__construct($organizerId);

        $this->title = $title;
        $this->language = $language;
    }

    public function getTitle(): Title
    {
        return new Title($this->title);
    }

    public function getLanguage(): Language
    {
        return new Language($this->language);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->title,
            'language' => $this->language,
        ];
    }

    public static function deserialize(array $data): TitleTranslated
    {
        return new static(
            $data['organizer_id'],
            new Title($data['title']),
            new Language($data['language'])
        );
    }
}
