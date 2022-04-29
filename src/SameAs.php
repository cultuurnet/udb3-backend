<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

class SameAs implements SameAsInterface
{
    private SluggerInterface $slugger;

    public function __construct()
    {
        $this->slugger = new CulturefeedSlugger();
    }
    public function generateSameAs(string $eventId, string $name): array
    {
        $eventSlug = $this->slugger->slug($name);
        return [
            'http://www.uitinvlaanderen.be/agenda/e/' . $eventSlug . '/' . $eventId,
        ];
    }
}
