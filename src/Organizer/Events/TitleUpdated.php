<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

final class TitleUpdated extends OrganizerEvent
{
    private string $title;

    public function __construct(
        string $organizerId,
        string $title
    ) {
        parent::__construct($organizerId);
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->title,
        ];
    }

    public static function deserialize(array $data): TitleUpdated
    {
        return new static(
            $data['organizer_id'],
            $data['title']
        );
    }
}
