<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

final class EducationalDescriptionUpdated extends OrganizerEvent
{
    private string $educationalDescription;
    private string $language;

    public function __construct(
        string $organizerId,
        string $educationalDescription,
        string $language
    ) {
        parent::__construct($organizerId);

        $this->educationalDescription = $educationalDescription;
        $this->language = $language;
    }

    public function getEducationalDescription(): string
    {
        return $this->educationalDescription;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'educational_description' => $this->educationalDescription,
            'language' => $this->language,
        ];
    }

    public static function deserialize(array $data): EducationalDescriptionUpdated
    {
        return new EducationalDescriptionUpdated(
            $data['organizer_id'],
            $data['educational_description'],
            $data['language']
        );
    }
}
