<?php

namespace CultuurNet\UDB3\Organizer\Events;

use ValueObjects\Web\Url;

final class WebsiteUpdated extends OrganizerEvent
{
    /**
     * @var Url
     */
    private $website;

    public function __construct(
        string $organizerId,
        Url $website
    ) {
        parent::__construct($organizerId);
        $this->website = $website;
    }

    public function getWebsite(): Url
    {
        return $this->website;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'website' => (string) $this->getWebsite(),
        ];
    }

    public static function deserialize(array $data): WebsiteUpdated
    {
        return new static(
            $data['organizer_id'],
            Url::fromNative($data['website'])
        );
    }
}
