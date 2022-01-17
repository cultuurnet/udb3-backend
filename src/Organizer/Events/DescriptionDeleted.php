<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Serializer\Serializable;

final class DescriptionDeleted implements Serializable
{
    private string $organizerId;

    private string $language;

    public function __construct(string $organizerId, string $language)
    {
        $this->organizerId = $organizerId;
        $this->language = $language;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function serialize(): array
    {
        return [
            'organizerId' => $this->organizerId,
            'language' => $this->language,
        ];
    }

    public static function deserialize(array $data): DescriptionDeleted
    {
        return new DescriptionDeleted(
            $data['organizerId'],
            $data['language']
        );
    }
}
