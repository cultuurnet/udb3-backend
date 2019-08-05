<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Organizer;

use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\WebsiteLookupServiceInterface;
use Respect\Validation\Exceptions\CallbackException;
use Respect\Validation\Rules\Callback;
use ValueObjects\Web\Url as LegacyUrl;

class OrganizerHasUniqueUrlValidator extends Callback
{
    /**
     * @param OrganizerIDParser $organizerIDParser
     * @param WebsiteLookupServiceInterface $websiteLookupService
     */
    public function __construct(
        OrganizerIDParser $organizerIDParser,
        WebsiteLookupServiceInterface $websiteLookupService
    ) {
        $callback = function ($organizerData) use ($organizerIDParser, $websiteLookupService) {
            if (!is_array($organizerData) || !isset($organizerData['@id']) || !isset($organizerData['url'])) {
                // Required data is missing. This is handled by another validator.
                return true;
            }

            try {
                $idUrl = new Url($organizerData['@id']);
                $id = $organizerIDParser->fromUrl($idUrl)->toString();

                $url = LegacyUrl::fromNative((string) $organizerData['url']);
                $organizerId = $websiteLookupService->lookup($url);

                return is_null($organizerId) || $organizerId === $id;
            } catch (\InvalidArgumentException $e) {
                // The @id or url is invalid. This is handled by another validator.
                return true;
            }
        };

        parent::__construct($callback);

        $this->setTemplate('A different organizer with the same url already exists.');
    }

    /**
     * @return CallbackException
     */
    protected function createException()
    {
        return new CallbackException();
    }
}
