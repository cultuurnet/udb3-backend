<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLanguage(): string
    {
        return $this->language;
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
            $data['title'],
            $data['language']
        );
    }
}
