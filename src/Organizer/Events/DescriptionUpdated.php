<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

final class DescriptionUpdated extends OrganizerEvent
{
    private string $description;
    private string $language;

    public function __construct(
        string $organizerId,
        string $description,
        string $language
    ) {
        parent::__construct($organizerId);

        $this->description = $description;
        $this->language = $language;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'description' => $this->description,
            'language' => $this->language,
        ];
    }

    public static function deserialize(array $data): DescriptionUpdated
    {
        return new DescriptionUpdated(
            $data['organizer_id'],
            $data['description'],
            $data['language']
        );
    }
}
