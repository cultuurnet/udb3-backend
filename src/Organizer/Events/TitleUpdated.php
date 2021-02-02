<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Title;

final class TitleUpdated extends OrganizerEvent
{
    /**
     * @var Title
     */
    private $title;

    public function __construct(
        string $organizerId,
        Title $title
    ) {
        parent::__construct($organizerId);
        $this->title = $title;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->getTitle()->toNative(),
        ];
    }

    public static function deserialize(array $data): TitleUpdated
    {
        return new static(
            $data['organizer_id'],
            new Title($data['title'])
        );
    }
}
