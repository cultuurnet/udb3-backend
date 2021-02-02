<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;

final class TitleTranslated extends OrganizerEvent
{
    /**
     * @var Title
     */
    private $title;

    /**
     * @var Language
     */
    private $language;

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

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->getTitle()->toNative(),
            'language' => $this->getLanguage()->getCode(),
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
