<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use ValueObjects\Web\Url;

final class WebsiteUpdated extends OrganizerEvent
{
    private string $website;

    public function __construct(
        string $organizerId,
        string $website
    ) {
        parent::__construct($organizerId);
        $this->website = $website;
    }

    public function getWebsite(): Url
    {
        return Url::fromNative($this->website);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'website' => $this->website,
        ];
    }

    public static function deserialize(array $data): WebsiteUpdated
    {
        return new static(
            $data['organizer_id'],
            $data['website']
        );
    }
}
