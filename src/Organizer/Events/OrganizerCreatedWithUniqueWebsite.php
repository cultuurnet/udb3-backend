<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

final class OrganizerCreatedWithUniqueWebsite extends OrganizerEvent
{
    /**
     * @var Language
     */
    private $mainLanguage;

    /**
     * @var Url
     */
    private $website;

    /**
     * @var Title
     */
    private $title;

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

    public function getMainLanguage(): Language
    {
        return new Language($this->mainLanguage);
    }

    public function getWebsite(): Url
    {
        return Url::fromNative($this->website);
    }

    public function getTitle(): Title
    {
        return new Title($this->title);
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
